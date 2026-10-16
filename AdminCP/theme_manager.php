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
$error = '';

// Handle theme activation
if (isset($_POST['activate_theme'])) {
    $theme_id = $_POST['theme_id'];
    if (activateTheme($theme_id)) {
        $message = "Theme activated successfully!";
    } else {
        $error = "Failed to activate theme.";
    }
}

// Handle theme update
if (isset($_POST['update_theme'])) {
    $theme_id = $_POST['theme_id'];
    $primary_color = $_POST['primary_color'];
    $secondary_color = $_POST['secondary_color'];
    $accent_color = $_POST['accent_color'];

    if (updateTheme($theme_id, $primary_color, $secondary_color, $accent_color)) {
        $message = "Theme colors updated successfully!";
    } else {
        $error = "Failed to update theme.";
    }
}

// Handle new theme creation
if (isset($_POST['create_theme'])) {
    $theme_name = $_POST['theme_name'];
    $primary_color = $_POST['new_primary_color'];
    $secondary_color = $_POST['new_secondary_color'];
    $accent_color = $_POST['new_accent_color'];

    if (createTheme($theme_name, $primary_color, $secondary_color, $accent_color)) {
        $message = "New theme created successfully!";
    } else {
        $error = "Failed to create theme.";
    }
}

// Handle logo upload
if (isset($_POST['upload_logo']) && isset($_FILES['logo_file'])) {
    $upload_result = uploadLogo($_FILES['logo_file']);

    if ($upload_result['success']) {
        if (updateThemeLogo($upload_result['path'])) {
            $message = "Logo uploaded and updated successfully!";
        } else {
            $error = "Logo uploaded but failed to update theme.";
        }
    } else {
        $error = $upload_result['message'];
    }
}

// Handle hero image upload
if (isset($_POST['upload_hero_image']) && isset($_FILES['hero_image_file'])) {
    $upload_result = uploadHeroImage($_FILES['hero_image_file']);

    if ($upload_result['success']) {
        if (updateHeroImage($upload_result['path'])) {
            $message = "Hero image uploaded and updated successfully!";
        } else {
            $error = "Hero image uploaded but failed to update settings.";
        }
    } else {
        $error = $upload_result['message'];
    }
}

// Handle quick settings update
if (isset($_POST['update_quick_settings'])) {
    $settings_to_update = [
        'site_name' => $_POST['site_name'],
        'site_email' => $_POST['site_email'],
        'site_phone' => $_POST['site_phone'],
        'site_address' => $_POST['site_address']
    ];

    $update_success = true;
    foreach ($settings_to_update as $key => $value) {
        $key_escaped = mysqli_real_escape_string($conn, $key);
        $value_escaped = mysqli_real_escape_string($conn, $value);

        $check_query = "SELECT id FROM settings WHERE setting_key = '$key_escaped'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $update_query = "UPDATE settings SET setting_value = '$value_escaped' WHERE setting_key = '$key_escaped'";
        } else {
            $update_query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key_escaped', '$value_escaped')";
        }

        if (!mysqli_query($conn, $update_query)) {
            $update_success = false;
        }
    }

    if ($update_success) {
        $message = "School information updated successfully!";
    } else {
        $error = "Failed to update some settings.";
    }
}

// Get all themes
$themes = getAllThemes();
$active_theme = getActiveTheme();
$hero_content = getHeroContent();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Manager - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo @filemtime(__DIR__ . '/../css/style.css'); ?>">
    <link rel="stylesheet" href="../css/admin.css?v=<?php echo @filemtime(__DIR__ . '/../css/admin.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: var(--bg-light);
            padding: 30px;
        }
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .theme-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
        }
        .theme-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .theme-card.active {
            border: 3px solid var(--accent-color);
            box-shadow: 0 4px 15px rgba(11,166,201,0.3);
        }
        .active-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--accent-color);
            color: var(--primary-color);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .color-preview {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .color-box {
            flex: 1;
            height: 80px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        .color-input-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 15px;
        }
        .color-input-group label {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
        }
        .color-input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .color-input-wrapper input[type="color"] {
            width: 60px;
            height: 40px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
        }
        .color-input-wrapper input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-family: monospace;
        }
        .btn-group-theme {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-small {
            padding: 8px 20px;
            font-size: 14px;
        }
        .create-theme-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #F0FDF4;
            color: #166534;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container">
        <div class="header-section">
            <h1 style="color: var(--primary-color);"><i class="fas fa-palette"></i> Theme Manager</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Logo Upload Section -->
        <div class="create-theme-section">
            <h3 style="color: var(--primary-color); margin-bottom: 20px;">
                <i class="fas fa-image"></i> Upload School Logo
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: center;">
                <!-- Current Logo Preview -->
                <div style="text-align: center;">
                    <div style="background: linear-gradient(135deg, <?php echo $active_theme['primary_color']; ?>, <?php echo darkenColor($active_theme['primary_color'], 20); ?>); padding: 30px; border-radius: 12px;">
                        <img src="../<?php echo $active_theme['logo_path']; ?>" alt="Current Logo" style="max-width: 150px; max-height: 150px; background: white; padding: 15px; border-radius: 10px;" onerror="this.src='../images/logo.png'">
                    </div>
                    <p style="margin-top: 15px; color: var(--text-light); font-size: 14px;">Current Logo</p>
                </div>

                <!-- Upload Form -->
                <div>
                    <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label for="auto_AdminCP_theme_manager_php_1"><i class="fas fa-cloud-upload-alt"></i> Choose New Logo</label>
                            <input id="auto_AdminCP_theme_manager_php_1" type="file" name="logo_file" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" required style="padding: 10px; border: 2px dashed var(--primary-color); border-radius: 8px; width: 100%; cursor: pointer;">
                            <small style="color: var(--text-light); display: block; margin-top: 8px;">
                                <i class="fas fa-info-circle"></i> Supported formats: PNG, JPG, GIF, WEBP (Max 5MB)<br>
                                <i class="fas fa-lightbulb"></i> Recommended: 200x200 pixels, transparent background
                            </small>
                        </div>
                        <button type="submit" name="upload_logo" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload & Apply Logo
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Hero Image Upload Section -->
        <div class="create-theme-section">
            <h3 style="color: var(--primary-color); margin-bottom: 20px;">
                <i class="fas fa-image"></i> Upload Hero Background Image
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: center;">
                <!-- Current Hero Image Preview -->
                <div style="text-align: center;">
                    <div style="position: relative; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.2);">
                        <img src="../<?php echo $hero_content['image']; ?>" alt="Current Hero Image" style="width: 100%; height: 200px; object-fit: cover;" onerror="this.src='../images/fortschool.jpg'">
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent); padding: 15px; color: white;">
                            <small style="font-size: 12px;">Current Hero Background</small>
                        </div>
                    </div>
                </div>

                <!-- Upload Form -->
                <div>
                    <form method="POST" enctype="multipart/form-data"><?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label for="auto_AdminCP_theme_manager_php_2"><i class="fas fa-cloud-upload-alt"></i> Choose New Hero Image</label>
                            <input id="auto_AdminCP_theme_manager_php_2" type="file" name="hero_image_file" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" required style="padding: 10px; border: 2px dashed var(--primary-color); border-radius: 8px; width: 100%; cursor: pointer;">
                            <small style="color: var(--text-light); display: block; margin-top: 8px;">
                                <i class="fas fa-info-circle"></i> Supported formats: PNG, JPG, GIF, WEBP (Max 10MB)<br>
                                <i class="fas fa-lightbulb"></i> Recommended: 1920x600 pixels (widescreen), high quality
                            </small>
                        </div>
                        <button type="submit" name="upload_hero_image" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload & Apply Hero Image
                        </button>
                    </form>
                    <div style="margin-top: 15px; padding: 12px; background: #FEF6E7; border-left: 4px solid #F59E0B; border-radius: 5px;">
                        <p style="margin: 0; color: #975A16; font-size: 13px;">
                            <i class="fas fa-exclamation-triangle" style="color: #F59E0B;"></i>
                            <strong>Note:</strong> This is the large background image on your homepage. Choose a high-quality, wide image for best results.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Settings Section -->
        <div class="create-theme-section">
            <h3 style="color: var(--primary-color); margin-bottom: 20px;">
                <i class="fas fa-school"></i> School Information (Quick Edit)
            </h3>
            <form method="POST"><?php echo csrf_field(); ?>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_theme_manager_php_3"><i class="fas fa-graduation-cap"></i> School Name *</label>
                        <input id="auto_AdminCP_theme_manager_php_3" type="text" name="site_name" required value="<?php echo htmlspecialchars(getSetting('site_name', 'SIR SYED DEGREE COLLEGE CHOWKI AJK')); ?>" placeholder="Enter school name">
                    </div>

                    <div class="form-group">
                        <label for="auto_AdminCP_theme_manager_php_4"><i class="fas fa-envelope"></i> Email Address *</label>
                        <input id="auto_AdminCP_theme_manager_php_4" type="email" name="site_email" required value="<?php echo htmlspecialchars(getSetting('site_email', 'sphs.pk.148@gmail.com')); ?>" placeholder="email@example.com">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="auto_AdminCP_theme_manager_php_5"><i class="fas fa-phone"></i> Contact Number *</label>
                        <input id="auto_AdminCP_theme_manager_php_5" type="text" name="site_phone" required value="<?php echo htmlspecialchars(getSetting('site_phone', '03006700956')); ?>" placeholder="03001234567">
                    </div>

                    <div class="form-group">
                        <label for="auto_AdminCP_theme_manager_php_6"><i class="fas fa-map-marker-alt"></i> Address *</label>
                        <input id="auto_AdminCP_theme_manager_php_6" type="text" name="site_address" required value="<?php echo htmlspecialchars(getSetting('site_address', 'Rahim Yar Khan, Punjab, Pakistan')); ?>" placeholder="City, Province">
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" name="update_quick_settings" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update School Information
                    </button>
                    <a href="settings.php" class="btn btn-outline" style="margin-left: 10px;">
                        <i class="fas fa-cog"></i> Advanced Settings
                    </a>
                </div>
            </form>

            <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid var(--primary-color); border-radius: 5px;">
                <p style="margin: 0; color: var(--text-dark); font-size: 14px;">
                    <i class="fas fa-info-circle" style="color: var(--primary-color);"></i>
                    <strong>Note:</strong> Changes made here will instantly update on your website. For more options like social media links, statistics, and other settings, click "Advanced Settings" button above.
                </p>
            </div>
        </div>

        <!-- Current Active Theme Preview -->
        <div style="background: linear-gradient(135deg, <?php echo $active_theme['primary_color']; ?>, <?php echo darkenColor($active_theme['primary_color'], 20); ?>); padding: 40px; border-radius: 12px; color: white; margin-bottom: 30px; text-align: center;">
            <h2 style="color: white; margin-bottom: 10px;">Currently Active Theme</h2>
            <p style="color: <?php echo $active_theme['accent_color']; ?>; font-size: 24px; font-weight: bold; margin: 0;">
                <?php echo htmlspecialchars($active_theme['theme_name']); ?>
            </p>
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
                <div style="text-align: center;">
                    <div style="width: 50px; height: 50px; background: <?php echo $active_theme['primary_color']; ?>; border: 3px solid white; border-radius: 8px; margin: 0 auto 8px;"></div>
                    <small>Primary</small>
                </div>
                <div style="text-align: center;">
                    <div style="width: 50px; height: 50px; background: <?php echo $active_theme['secondary_color']; ?>; border: 3px solid white; border-radius: 8px; margin: 0 auto 8px;"></div>
                    <small>Secondary</small>
                </div>
                <div style="text-align: center;">
                    <div style="width: 50px; height: 50px; background: <?php echo $active_theme['accent_color']; ?>; border: 3px solid white; border-radius: 8px; margin: 0 auto 8px;"></div>
                    <small>Accent</small>
                </div>
            </div>
        </div>

        <!-- Create New Theme -->
        <div class="create-theme-section">
            <h3 style="color: var(--primary-color); margin-bottom: 20px;">
                <i class="fas fa-plus-circle"></i> Create New Theme
            </h3>
            <form method="POST"><?php echo csrf_field(); ?>
                <div class="form-group">
                    <label for="auto_AdminCP_theme_manager_php_7">Theme Name</label>
                    <input id="auto_AdminCP_theme_manager_php_7" type="text" name="theme_name" required placeholder="e.g., Ocean Blue Theme">
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <div class="color-input-group">
                        <label>Primary Color</label>
                        <div class="color-input-wrapper">
                            <input type="color" id="new_primary_color" name="new_primary_color" value="#102A56" onchange="updateTextInput(this, 'new_primary_text')">
                            <input type="text" id="new_primary_text" value="#102A56" onchange="updateColorInput(this, 'new_primary_color')">
                        </div>
                    </div>
                    <div class="color-input-group">
                        <label>Secondary Color</label>
                        <div class="color-input-wrapper">
                            <input type="color" id="new_secondary_color" name="new_secondary_color" value="#FFFFFF" onchange="updateTextInput(this, 'new_secondary_text')">
                            <input type="text" id="new_secondary_text" value="#FFFFFF" onchange="updateColorInput(this, 'new_secondary_color')">
                        </div>
                    </div>
                    <div class="color-input-group">
                        <label>Accent Color</label>
                        <div class="color-input-wrapper">
                            <input type="color" id="new_accent_color" name="new_accent_color" value="#0BA6C9" onchange="updateTextInput(this, 'new_accent_text')">
                            <input type="text" id="new_accent_text" value="#0BA6C9" onchange="updateColorInput(this, 'new_accent_color')">
                        </div>
                    </div>
                </div>
                <button type="submit" name="create_theme" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> Create Theme
                </button>
            </form>
        </div>

        <!-- Existing Themes -->
        <h3 style="color: var(--primary-color); margin-bottom: 20px;">
            <i class="fas fa-swatchbook"></i> Available Themes (Click to Apply)
        </h3>
        <div class="theme-grid">
            <?php foreach ($themes as $theme): ?>
                <div class="theme-card <?php echo $theme['is_active'] ? 'active' : ''; ?>">
                    <?php if ($theme['is_active']): ?>
                        <div class="active-badge">
                            <i class="fas fa-check"></i> ACTIVE
                        </div>
                    <?php endif; ?>

                    <h4 style="color: var(--primary-color); margin-bottom: 15px;">
                        <?php echo htmlspecialchars($theme['theme_name']); ?>
                    </h4>

                    <div class="color-preview">
                        <div class="color-box" style="background: <?php echo $theme['primary_color']; ?>">
                            Primary
                        </div>
                        <div class="color-box" style="background: <?php echo $theme['secondary_color']; ?>; color: #243247;">
                            Secondary
                        </div>
                        <div class="color-box" style="background: <?php echo $theme['accent_color']; ?>; color: #243247;">
                            Accent
                        </div>
                    </div>

                    <!-- Edit Colors Form -->
                    <form method="POST" style="margin-top: 20px;"><?php echo csrf_field(); ?>
                        <input type="hidden" name="theme_id" value="<?php echo $theme['id']; ?>">

                        <div class="color-input-group">
                            <label>Primary Color</label>
                            <div class="color-input-wrapper">
                                <input type="color" name="primary_color" value="<?php echo $theme['primary_color']; ?>">
                                <input type="text" name="primary_color" value="<?php echo $theme['primary_color']; ?>" readonly>
                            </div>
                        </div>

                        <div class="color-input-group">
                            <label>Secondary Color</label>
                            <div class="color-input-wrapper">
                                <input type="color" name="secondary_color" value="<?php echo $theme['secondary_color']; ?>">
                                <input type="text" name="secondary_color" value="<?php echo $theme['secondary_color']; ?>" readonly>
                            </div>
                        </div>

                        <div class="color-input-group">
                            <label>Accent Color</label>
                            <div class="color-input-wrapper">
                                <input type="color" name="accent_color" value="<?php echo $theme['accent_color']; ?>">
                                <input type="text" name="accent_color" value="<?php echo $theme['accent_color']; ?>" readonly>
                            </div>
                        </div>

                        <div class="btn-group-theme">
                            <?php if (!$theme['is_active']): ?>
                                <button type="submit" name="activate_theme" class="btn btn-primary btn-small">
                                    <i class="fas fa-check"></i> Activate
                                </button>
                            <?php endif; ?>
                            <button type="submit" name="update_theme" class="btn btn-outline btn-small">
                                <i class="fas fa-save"></i> Update Colors
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function updateTextInput(colorInput, textInputId) {
            document.getElementById(textInputId).value = colorInput.value.toUpperCase();
        }

        function updateColorInput(textInput, colorInputId) {
            document.getElementById(colorInputId).value = textInput.value;
        }
    </script>
    </div>
</body>
</html>

<?php
// Helper function to darken color
function darkenColor($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, $r - ($r * $percent / 100));
    $g = max(0, $g - ($g * $percent / 100));
    $b = max(0, $b - ($b * $percent / 100));

    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
               . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
               . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
?>
