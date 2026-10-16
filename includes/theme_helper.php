<?php
/**
 * Theme Helper Functions
 * Manages dynamic theme colors and logo
 */

// Get active theme
function getActiveTheme() {
    global $conn;

    $query = "SELECT * FROM themes WHERE is_active = 1 LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }

    // Default theme if none active
    return [
        'theme_name' => 'Default Theme',
        'primary_color' => '#102A56',
        'secondary_color' => '#FFFFFF',
        'accent_color' => '#0BA6C9',
        'logo_path' => 'images/logo.png'
    ];
}

// Get all themes
function getAllThemes() {
    global $conn;

    $query = "SELECT * FROM themes ORDER BY id ASC";
    $result = mysqli_query($conn, $query);

    $themes = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $themes[] = $row;
        }
    }

    return $themes;
}

// Activate theme
function activateTheme($theme_id) {
    global $conn;

    // Deactivate all themes
    mysqli_query($conn, "UPDATE themes SET is_active = 0");

    // Activate selected theme
    $theme_id = mysqli_real_escape_string($conn, $theme_id);
    $query = "UPDATE themes SET is_active = 1 WHERE id = '$theme_id'";

    return mysqli_query($conn, $query);
}

// Generate CSS variables for active theme
function getThemeCSS() {
    $theme = getActiveTheme();

    $css = ":root {\n";
    $css .= "    --primary-color: " . $theme['primary_color'] . ";\n";
    $css .= "    --secondary-color: " . $theme['secondary_color'] . ";\n";
    $css .= "    --accent-color: " . $theme['accent_color'] . ";\n";
    $css .= "}\n";

    return $css;
}

// Get logo path
function getLogoPath() {
    $theme = getActiveTheme();
    return $theme['logo_path'] ?? 'images/logo.png';
}

// Update theme colors
function updateTheme($theme_id, $primary_color, $secondary_color, $accent_color) {
    global $conn;

    $theme_id = mysqli_real_escape_string($conn, $theme_id);
    $primary_color = mysqli_real_escape_string($conn, $primary_color);
    $secondary_color = mysqli_real_escape_string($conn, $secondary_color);
    $accent_color = mysqli_real_escape_string($conn, $accent_color);

    $query = "UPDATE themes SET
              primary_color = '$primary_color',
              secondary_color = '$secondary_color',
              accent_color = '$accent_color',
              updated_at = NOW()
              WHERE id = '$theme_id'";

    return mysqli_query($conn, $query);
}

// Create new theme
function createTheme($theme_name, $primary_color, $secondary_color, $accent_color, $logo_path = null) {
    global $conn;

    $theme_name = mysqli_real_escape_string($conn, $theme_name);
    $primary_color = mysqli_real_escape_string($conn, $primary_color);
    $secondary_color = mysqli_real_escape_string($conn, $secondary_color);
    $accent_color = mysqli_real_escape_string($conn, $accent_color);
    $logo_path = $logo_path ? mysqli_real_escape_string($conn, $logo_path) : 'images/logo.png';

    $query = "INSERT INTO themes (theme_name, primary_color, secondary_color, accent_color, logo_path, is_active)
              VALUES ('$theme_name', '$primary_color', '$secondary_color', '$accent_color', '$logo_path', 0)";

    return mysqli_query($conn, $query);
}

// Update logo for active theme
function updateThemeLogo($logo_path) {
    global $conn;

    $logo_path = mysqli_real_escape_string($conn, $logo_path);

    // Update logo path for active theme
    $query = "UPDATE themes SET logo_path = '$logo_path', updated_at = NOW() WHERE is_active = 1";

    return mysqli_query($conn, $query);
}

// Upload logo file
function uploadLogo($file) {
    $upload_dir = '../images/logos/';

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'];
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        compressUploadedImage($filepath);
        // Return path relative to website root
        return ['success' => true, 'path' => 'images/logos/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Upload hero image
function uploadHeroImage($file) {
    $upload_dir = '../images/hero/';

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 10 * 1024 * 1024; // 10MB for hero images

    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'];
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Maximum size is 10MB.'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'hero_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Upload file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        compressUploadedImage($filepath);
        // Return path relative to website root
        return ['success' => true, 'path' => 'images/hero/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

// Update hero image in settings
function updateHeroImage($image_path) {
    global $conn;

    $image_path = mysqli_real_escape_string($conn, $image_path);

    $check_query = "SELECT id FROM settings WHERE setting_key = 'hero_image'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $query = "UPDATE settings SET setting_value = '$image_path' WHERE setting_key = 'hero_image'";
    } else {
        $query = "INSERT INTO settings (setting_key, setting_value) VALUES ('hero_image', '$image_path')";
    }

    return mysqli_query($conn, $query);
}
?>
