<?php
/**
 * Shared image-compression helper — call this on every uploaded image right
 * after move_uploaded_file() succeeds, across both the public site and
 * AdminCP, so every image saved to the site is automatically re-compressed
 * and capped to a sane maximum size. Non-image files (PDF, DOC, ZIP, etc.)
 * and GIFs (to avoid breaking animation) are left untouched — this is a safe
 * no-op for those, never an error.
 *
 * @param string $filePath   Absolute or relative path to the file already on disk.
 * @param int    $maxWidth   Images wider than this are downscaled (default 1920px).
 * @param int    $maxHeight  Images taller than this are downscaled (default 1920px).
 * @param int    $quality    JPEG/WebP quality 0-100 (default 82 — visually
 *                            lossless for web use, typically 60-85% smaller
 *                            than an uncompressed/high-quality original).
 * @return bool True on success or safe no-op, false only on a genuine failure
 *              to process what really is an image.
 */
function compressUploadedImage($filePath, $maxWidth = 1920, $maxHeight = 1920, $quality = 82) {
    if (!is_file($filePath)) return false;
    // Without PHP's GD extension there is nothing we can do safely: keep the file untouched
    if (!function_exists('imagecreatetruecolor')) return true;

    $info = @getimagesize($filePath);
    if ($info === false) return true; // not an image (PDF, DOC, ZIP, …) — nothing to do

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $src = @imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($filePath);
            break;
        case 'image/webp':
            $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : false;
            break;
        default:
            // GIF (preserve animation), BMP, etc. — leave completely untouched.
            return true;
    }
    if (!$src) return false;

    $origW = imagesx($src);
    $origH = imagesy($src);
    $ratio = min(1, $maxWidth / $origW, $maxHeight / $origH);

    if ($ratio < 1) {
        $newW = max(1, (int) round($origW * $ratio));
        $newH = max(1, (int) round($origH * $ratio));
        $resized = imagecreatetruecolor($newW, $newH);
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);
        $src = $resized;
    }

    $ok = false;
    switch ($mime) {
        case 'image/jpeg':
            $ok = imagejpeg($src, $filePath, $quality);
            break;
        case 'image/png':
            // PNG has no 0-100 quality scale — 6 is a balanced compression level (0-9).
            $ok = imagepng($src, $filePath, 6);
            break;
        case 'image/webp':
            $ok = function_exists('imagewebp') ? imagewebp($src, $filePath, $quality) : false;
            break;
    }
    imagedestroy($src);
    return $ok;
}
