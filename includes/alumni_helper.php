<?php
/**
 * Alumni Portal — shared helpers for the public registration page (alumni.php)
 * and AdminCP > Alumni Portal (AdminCP/manage_alumni.php).
 *
 * Column map of the existing `alumni` table:
 *   student_name = Full name           current_job    = Current occupation
 *   father_name  = Father's name       job_department = Current organization
 *   course       = Last class/program  job_city       = City
 *   review       = Additional info     (+ email, country, show_public, admin_notes, updated_at added below)
 */

const ALUMNI_STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
const ALUMNI_PHOTO_DIR = 'uploads/alumni/';
const ALUMNI_PHOTO_MAX = 2 * 1024 * 1024; // 2 MB

/**
 * Adds the Alumni Portal columns/indexes to the existing `alumni` table when
 * they are missing, so the live database upgrades itself without a manual SQL
 * step. Runs a single SHOW COLUMNS query once the table is up to date.
 */
function alumni_ensure_schema($conn) {
    static $done = false;
    if ($done) return;
    $done = true;

    $res = mysqli_query($conn, "SHOW COLUMNS FROM alumni");
    if (!$res) return;
    $cols = [];
    while ($r = mysqli_fetch_assoc($res)) $cols[$r['Field']] = $r;

    $alter = [];
    if (!isset($cols['email']))       $alter[] = "ADD COLUMN email VARCHAR(150) NULL AFTER father_name";
    if (!isset($cols['country']))     $alter[] = "ADD COLUMN country VARCHAR(100) NULL AFTER job_city";
    if (!isset($cols['show_public'])) $alter[] = "ADD COLUMN show_public TINYINT(1) NOT NULL DEFAULT 0 AFTER review";
    if (!isset($cols['admin_notes'])) $alter[] = "ADD COLUMN admin_notes TEXT NULL AFTER status";
    if (!isset($cols['updated_at']))  $alter[] = "ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
    if (isset($cols['review']) && strtoupper($cols['review']['Null']) === 'NO') $alter[] = "MODIFY review TEXT NULL";
    if ($alter) mysqli_query($conn, "ALTER TABLE alumni " . implode(', ', $alter));

    $idx = [];
    $ires = mysqli_query($conn, "SHOW INDEX FROM alumni");
    while ($ires && ($r = mysqli_fetch_assoc($ires))) $idx[$r['Key_name']] = true;
    if (empty($idx['idx_alumni_email']))  mysqli_query($conn, "ALTER TABLE alumni ADD INDEX idx_alumni_email (email)");
    if (empty($idx['idx_alumni_mobile'])) mysqli_query($conn, "ALTER TABLE alumni ADD INDEX idx_alumni_mobile (mobile_number)");
    if (empty($idx['idx_alumni_status'])) mysqli_query($conn, "ALTER TABLE alumni ADD INDEX idx_alumni_status (status, passing_year)");
}

/** "Last class or program attended" choices: current Matric/Intermediate programs + common extras. */
function alumni_program_options($conn) {
    $opts = [];
    $res = mysqli_query($conn, "SELECT name FROM courses WHERE status='active' AND category IN ('matric','inter') ORDER BY display_order ASC, id ASC");
    while ($res && ($r = mysqli_fetch_assoc($res))) $opts[] = $r['name'];
    foreach (['Matric (Arts / General)', 'Middle (Class 8)'] as $extra) {
        if (!in_array($extra, $opts, true)) $opts[] = $extra;
    }
    return $opts;
}

/** Pakistani numbers become 03XXXXXXXXX; other international numbers keep a leading +. Returns '' when invalid. */
function alumni_normalize_mobile($raw) {
    $m = preg_replace('/[\s\-().]/', '', (string) $raw);
    if (preg_match('/^(?:\+92|0092|92)(3\d{9})$/', $m, $x)) return '0' . $x[1];
    if (preg_match('/^03\d{9}$/', $m)) return $m;
    if (preg_match('/^(?:\+|00)([1-9]\d{7,14})$/', $m, $x)) return '+' . $x[1];
    return '';
}

/** Trimmed, length-limited single-line text. */
function alumni_clean($v, $max) {
    $v = trim(preg_replace('/\s+/u', ' ', (string) $v));
    return mb_substr($v, 0, $max);
}

/**
 * Validates registration/edit input. Returns [$data, $errors].
 * $data keys match the table columns.
 */
function alumni_validate(array $in, array $program_options, $is_admin = false) {
    $e = [];
    $d = [];
    $year_now = (int) date('Y');

    $d['student_name'] = alumni_clean($in['full_name'] ?? '', 100);
    if ($d['student_name'] === '') $e['full_name'] = 'Full name is required.';
    elseif (!preg_match("/^[\p{L}][\p{L}\s.'-]{1,99}$/u", $d['student_name'])) $e['full_name'] = 'Please enter a valid name (letters only).';

    $d['father_name'] = alumni_clean($in['father_name'] ?? '', 100);
    if ($d['father_name'] === '') $e['father_name'] = "Father's name is required.";
    elseif (!preg_match("/^[\p{L}][\p{L}\s.'-]{1,99}$/u", $d['father_name'])) $e['father_name'] = "Please enter a valid father's name.";

    $d['email'] = strtolower(alumni_clean($in['email'] ?? '', 150));
    if ($d['email'] === '') $e['email'] = 'Email address is required.';
    elseif (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $e['email'] = 'Please enter a valid email address.';

    $d['mobile_number'] = alumni_normalize_mobile($in['mobile'] ?? '');
    if (trim($in['mobile'] ?? '') === '') $e['mobile'] = 'Mobile number is required.';
    elseif ($d['mobile_number'] === '') $e['mobile'] = 'Enter a valid mobile number, e.g. 03001234567 or +971501234567.';

    $year = trim($in['passing_year'] ?? '');
    if ($year === '') $e['passing_year'] = 'Passing year is required.';
    elseif (!preg_match('/^\d{4}$/', $year) || (int) $year < 1990 || (int) $year > $year_now) $e['passing_year'] = 'Choose a passing year between 1990 and ' . $year_now . '.';
    $d['passing_year'] = (int) $year;

    $prog = alumni_clean($in['program'] ?? '', 100);
    $other = alumni_clean($in['program_other'] ?? '', 100);
    if ($prog === 'Other') $prog = $other;
    elseif ($prog !== '' && !$is_admin && !in_array($prog, $program_options, true)) $prog = '';
    $d['course'] = $prog;
    if ($d['course'] === '') $e['program'] = (($in['program'] ?? '') === 'Other') ? 'Please write the class or program you attended.' : 'Please choose your last class or program.';

    $d['current_job']    = alumni_clean($in['occupation'] ?? '', 150);
    $d['job_department'] = alumni_clean($in['organization'] ?? '', 150);

    $d['job_city'] = alumni_clean($in['city'] ?? '', 100);
    if ($d['job_city'] === '') $e['city'] = 'City is required.';
    $d['country'] = alumni_clean($in['country'] ?? '', 100);
    if ($d['country'] === '') $e['country'] = 'Country is required.';

    $info = trim(str_replace("\r\n", "\n", (string) ($in['additional_info'] ?? '')));
    if (mb_strlen($info) > 1000) $e['additional_info'] = 'Additional information must be 1000 characters or fewer.';
    $d['review'] = mb_substr($info, 0, 1000);

    $d['show_public'] = !empty($in['show_public']) ? 1 : 0;

    return [$d, $e];
}

/**
 * Finds an existing (non-rejected) registration with the same email or mobile.
 * $exclude_id lets the admin edit form ignore the record being edited.
 */
function alumni_find_duplicate($conn, $email, $mobile, $exclude_id = 0) {
    $stmt = mysqli_prepare($conn, "SELECT id, status FROM alumni WHERE (email = ? OR mobile_number = ?) AND status <> 'rejected' AND id <> ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ssi', $email, $mobile, $exclude_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

/**
 * Validates and stores an uploaded profile photo. Returns [path|null, error|null].
 * The stored name is generated (never the client's filename) and the content is
 * checked with getimagesize(); uploads/ already blocks script execution.
 */
function alumni_save_photo($file, $base_dir = '') {
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'The photo could not be uploaded. Please try again.'];
    if ($file['size'] > ALUMNI_PHOTO_MAX) return [null, 'Photo must be smaller than 2 MB.'];
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) return [null, 'Photo must be a JPG, PNG or WebP image.'];

    $dir = $base_dir . ALUMNI_PHOTO_DIR;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = 'alumni_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) return [null, 'The photo could not be saved. Please try again.'];
    if (function_exists('compressUploadedImage')) compressUploadedImage($dir . $name, 800, 800);
    return [ALUMNI_PHOTO_DIR . $name, null];
}

/** Deletes a stored alumni photo (only inside uploads/alumni/). */
function alumni_delete_photo($path, $base_dir = '') {
    if (!$path || strpos($path, ALUMNI_PHOTO_DIR) !== 0 || strpos($path, '..') !== false) return;
    $full = $base_dir . $path;
    if (is_file($full)) @unlink($full);
}
