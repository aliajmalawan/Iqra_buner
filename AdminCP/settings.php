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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = true;

    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            $key_escaped = mysqli_real_escape_string($conn, $key);
            $value_escaped = mysqli_real_escape_string($conn, $value);

            // Check if setting exists
            $check_query = "SELECT id FROM settings WHERE setting_key = '$key_escaped'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                // Update existing setting
                $update_query = "UPDATE settings SET setting_value = '$value_escaped' WHERE setting_key = '$key_escaped'";
                if (!mysqli_query($conn, $update_query)) {
                    $success = false;
                }
            } else {
                // Insert new setting
                $insert_query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key_escaped', '$value_escaped')";
                if (!mysqli_query($conn, $insert_query)) {
                    $success = false;
                }
            }
        }
    }

    if ($success) {
        $message = "Settings saved successfully!";
    } else {
        $message = "Error saving some settings.";
    }
}

// Load settings using helper function
loadSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .settings-section h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-color);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="color: var(--primary-color);"><i class="fas fa-cog"></i> Site Settings</h1>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#FEF2F2' : '#F0FDF4'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#991B1B' : '#166534'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST"><?php echo csrf_field(); ?>
            <!-- General Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-info-circle"></i> General Information</h3>

                <div class="form-group">
                    <label for="auto_AdminCP_settings_php_1">Site Name *</label>
                    <input id="auto_AdminCP_settings_php_1" type="text" name="site_name" required value="<?php echo htmlspecialchars(getSetting('site_name', 'Fort Education System')); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="auto_AdminCP_settings_php_2">Site Email *</label>
                        <input id="auto_AdminCP_settings_php_2" type="email" name="site_email" required value="<?php echo htmlspecialchars(getSetting('site_email', 'info@fort.edu.pk')); ?>">
                    </div>

                    <div class="form-group">
                        <label for="auto_AdminCP_settings_php_3">Site Phone *</label>
                        <input id="auto_AdminCP_settings_php_3" type="text" name="site_phone" required value="<?php echo htmlspecialchars(getSetting('site_phone', '0306-1345242')); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_settings_php_4">Site Address *</label>
                    <textarea id="auto_AdminCP_settings_php_4" name="site_address" required rows="3"><?php echo htmlspecialchars(getSetting('site_address', 'Basti Malook, Multan, Pakistan')); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_settings_php_5">Office Hours</label>
                    <input id="auto_AdminCP_settings_php_5" type="text" name="office_hours" value="<?php echo htmlspecialchars(getSetting('office_hours', 'Mon - Sat: 8:00 AM - 2:00 PM')); ?>" placeholder="e.g., Mon - Sat: 8:00 AM - 5:00 PM">
                </div>
            </div>

            <!-- Social Media Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-share-alt"></i> Social Media Links</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="auto_AdminCP_settings_php_6"><i class="fab fa-facebook"></i> Facebook URL</label>
                        <input id="auto_AdminCP_settings_php_6" type="url" name="facebook_url" value="<?php echo htmlspecialchars(getSetting('facebook_url')); ?>" placeholder="https://facebook.com/yourpage">
                    </div>

                    <div class="form-group">
                        <label for="auto_AdminCP_settings_php_7"><i class="fab fa-instagram"></i> Instagram URL</label>
                        <input id="auto_AdminCP_settings_php_7" type="url" name="instagram_url" value="<?php echo htmlspecialchars(getSetting('instagram_url')); ?>" placeholder="https://instagram.com/yourpage">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="auto_AdminCP_settings_php_8"><i class="fab fa-youtube"></i> YouTube URL</label>
                        <input id="auto_AdminCP_settings_php_8" type="url" name="youtube_url" value="<?php echo htmlspecialchars(getSetting('youtube_url')); ?>" placeholder="https://youtube.com/yourchannel">
                    </div>

                    <div class="form-group">
                        <label for="auto_AdminCP_settings_php_9"><i class="fab fa-twitter"></i> Twitter URL</label>
                        <input id="auto_AdminCP_settings_php_9" type="url" name="twitter_url" value="<?php echo htmlspecialchars(getSetting('twitter_url')); ?>" placeholder="https://twitter.com/yourpage">
                    </div>
                </div>
            </div>

            <!-- Note: Home page hero text & statistics numbers moved to AdminCP > Home Page > General & Stats tab -->
            <!-- Note: About page description, stats, mission & vision moved to AdminCP > About > respective tabs -->

            <!-- Footer Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-shoe-prints"></i> Footer Content</h3>

                <div class="form-group">
                    <label for="auto_AdminCP_settings_php_10">Footer About Text</label>
                    <textarea id="auto_AdminCP_settings_php_10" name="footer_about_text" rows="3" placeholder="Short description in footer"><?php echo htmlspecialchars(getSetting('footer_about_text', 'Fort Education System is dedicated to providing quality education and nurturing the potential of every student.')); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_settings_php_11">Copyright Text</label>
                    <input id="auto_AdminCP_settings_php_11" type="text" name="copyright_text" value="<?php echo htmlspecialchars(getSetting('copyright_text', 'Fort Education System. All rights reserved.')); ?>" placeholder="Copyright text in footer">
                </div>
            </div>

            <!-- Contact Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-map-marker-alt"></i> Contact Information</h3>

                <div class="form-group">
                    <label for="auto_AdminCP_settings_php_12">Google Maps Embed Code (Optional)</label>
                    <textarea id="auto_AdminCP_settings_php_12" name="google_maps_embed" rows="3" placeholder="Paste Google Maps embed iframe code here"><?php echo htmlspecialchars(getSetting('google_maps_embed')); ?></textarea>
                    <small style="color: var(--text-light); display: block; margin-top: 5px;">
                        Get embed code from Google Maps → Share → Embed a map
                    </small>
                </div>

                <div class="form-group">
                    <label for="auto_AdminCP_settings_php_13">WhatsApp Number (for quick contact)</label>
                    <input id="auto_AdminCP_settings_php_13" type="text" name="whatsapp_number" value="<?php echo htmlspecialchars(getSetting('whatsapp_number')); ?>" placeholder="e.g., +923061345242">
                    <small style="color: var(--text-light); display: block; margin-top: 5px;">
                        Include country code (e.g., +92 for Pakistan)
                    </small>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" name="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>
    </div>
</body>
</html>
