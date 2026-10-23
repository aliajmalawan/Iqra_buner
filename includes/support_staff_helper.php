<?php
/**
 * Support Staff (non-teaching) — helpers for AdminCP > Support Staff
 * (AdminCP/manage_support_staff.php). Kept in its own `support_staff` table:
 * the `faculty` table is the public teacher directory and is not shared.
 */

const STAFF_CATEGORIES = [
    'driver'           => 'Driver',
    'class_iv'         => 'Class IV Employee',
    'security'         => 'Security Staff',
    'office_assistant' => 'Office Assistant',
    'cleaner'          => 'Cleaner',
    'other'            => 'Other Support Staff',
];
const STAFF_EMPLOYMENT = [
    'permanent'  => 'Permanent',
    'contract'   => 'Contract',
    'daily_wage' => 'Daily Wage',
    'on_leave'   => 'On Leave',
    'resigned'   => 'Resigned',
    'retired'    => 'Retired',
    'terminated' => 'Terminated',
];
const STAFF_PHOTO_DIR = 'uploads/staff/';
const STAFF_PHOTO_MAX = 2 * 1024 * 1024;

/** Creates the table on first use, so the live database needs no manual SQL step. */
function staff_ensure_schema($conn) {
    static $done = false;
    if ($done) return;
    $done = true;
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS support_staff (
        id INT(11) NOT NULL AUTO_INCREMENT,
        employee_id VARCHAR(30) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        father_name VARCHAR(100) NOT NULL,
        cnic VARCHAR(15) NOT NULL,
        mobile VARCHAR(20) NOT NULL,
        address VARCHAR(255) NOT NULL,
        category VARCHAR(30) NOT NULL DEFAULT 'other',
        designation VARCHAR(100) NULL,
        campus VARCHAR(100) NULL,
        joining_date DATE NULL,
        employment_status VARCHAR(20) NOT NULL DEFAULT 'permanent',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        photo VARCHAR(255) NULL,
        emergency_name VARCHAR(100) NULL,
        emergency_phone VARCHAR(20) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_staff_employee_id (employee_id),
        UNIQUE KEY uq_staff_cnic (cnic),
        KEY idx_staff_filters (category, employment_status, is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

/** Next suggested employee ID: SS-0001, SS-0002 … (admins can type their own). */
function staff_next_employee_id($conn) {
    $res = mysqli_query($conn, "SELECT employee_id FROM support_staff WHERE employee_id REGEXP '^SS-[0-9]+$' ORDER BY CAST(SUBSTRING(employee_id, 4) AS UNSIGNED) DESC LIMIT 1");
    $row = $res ? db_fetch_row($res) : null;
    $n = $row ? ((int) substr($row[0], 3)) + 1 : 1;
    return 'SS-' . str_pad($n, 4, '0', STR_PAD_LEFT);
}

function staff_campus_options($conn) {
    $out = [];
    $res = mysqli_query($conn, "SELECT name FROM campuses WHERE status = 'active' ORDER BY display_order, id");
    while ($res && ($r = db_fetch_row($res))) $out[] = $r[0];
    return $out;
}

function staff_clean($v, $max) {
    return mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $v)), 0, $max);
}

/** 13 digits → 12345-1234567-1; '' when invalid. */
function staff_format_cnic($raw) {
    $d = preg_replace('/\D/', '', (string) $raw);
    return strlen($d) === 13 ? substr($d, 0, 5) . '-' . substr($d, 5, 7) . '-' . substr($d, 12) : '';
}

/** Pakistani mobile → 03XXXXXXXXX; also accepts landlines/international with a leading +. '' when invalid. */
function staff_format_phone($raw) {
    $m = preg_replace('/[\s\-().]/', '', (string) $raw);
    if (preg_match('/^(?:\+92|0092|92)(3\d{9})$/', $m, $x)) return '0' . $x[1];
    if (preg_match('/^0\d{9,10}$/', $m)) return $m;
    if (preg_match('/^(?:\+|00)([1-9]\d{7,14})$/', $m, $x)) return '+' . $x[1];
    return '';
}

/** Masks personal data for users without full access. */
function staff_mask_cnic($c)  { return $c ? substr($c, 0, 5) . '-*******-' . substr($c, -1) : ''; }
function staff_mask_phone($p) { return $p ? substr($p, 0, 4) . str_repeat('*', max(0, strlen($p) - 6)) . substr($p, -2) : ''; }

/** Validates the add/edit form. Returns [$data, $errors]. */
function staff_validate(array $in, array $campuses) {
    $e = []; $d = [];

    $d['employee_id'] = strtoupper(staff_clean($in['employee_id'] ?? '', 30));
    if ($d['employee_id'] === '') $e['employee_id'] = 'Employee ID is required.';
    elseif (!preg_match('/^[A-Z0-9][A-Z0-9\-\/]{1,29}$/', $d['employee_id'])) $e['employee_id'] = 'Use letters, numbers, "-" or "/" only (e.g. SS-0001).';

    foreach (['full_name' => 'Full name', 'father_name' => "Father's name"] as $k => $lbl) {
        $d[$k] = staff_clean($in[$k] ?? '', 100);
        if ($d[$k] === '') $e[$k] = $lbl . ' is required.';
        elseif (!preg_match("/^[\p{L}][\p{L}\s.'-]{1,99}$/u", $d[$k])) $e[$k] = 'Please enter a valid ' . strtolower($lbl) . ' (letters only).';
    }

    $d['cnic'] = staff_format_cnic($in['cnic'] ?? '');
    if (trim($in['cnic'] ?? '') === '') $e['cnic'] = 'CNIC is required.';
    elseif ($d['cnic'] === '') $e['cnic'] = 'CNIC must be 13 digits, e.g. 15101-1234567-1.';

    $d['mobile'] = staff_format_phone($in['mobile'] ?? '');
    if (trim($in['mobile'] ?? '') === '') $e['mobile'] = 'Mobile number is required.';
    elseif ($d['mobile'] === '') $e['mobile'] = 'Enter a valid mobile number, e.g. 03001234567.';

    $d['address'] = staff_clean($in['address'] ?? '', 255);
    if (mb_strlen($d['address']) < 5) $e['address'] = 'Please enter the residential address.';

    $d['category'] = $in['category'] ?? '';
    if (!isset(STAFF_CATEGORIES[$d['category']])) $e['category'] = 'Choose a staff category.';
    $d['designation'] = staff_clean($in['designation'] ?? '', 100);

    $d['campus'] = staff_clean($in['campus'] ?? '', 100);
    if ($d['campus'] !== '' && !in_array($d['campus'], $campuses, true)) $d['campus'] = '';

    $jd = trim((string) ($in['joining_date'] ?? ''));
    $d['joining_date'] = null;
    if ($jd === '') $e['joining_date'] = 'Joining date is required.';
    else {
        $dt = DateTime::createFromFormat('Y-m-d', $jd);
        if (!$dt || $dt->format('Y-m-d') !== $jd || $jd < '1990-01-01' || $jd > date('Y-m-d', strtotime('+1 year'))) $e['joining_date'] = 'Enter a valid joining date.';
        else $d['joining_date'] = $jd;
    }

    $d['employment_status'] = $in['employment_status'] ?? '';
    if (!isset(STAFF_EMPLOYMENT[$d['employment_status']])) $e['employment_status'] = 'Choose the employment status.';

    $d['emergency_name'] = staff_clean($in['emergency_name'] ?? '', 100);
    $ep = trim((string) ($in['emergency_phone'] ?? ''));
    $d['emergency_phone'] = $ep === '' ? '' : staff_format_phone($ep);
    if ($ep !== '' && $d['emergency_phone'] === '') $e['emergency_phone'] = 'Enter a valid emergency contact number.';

    $d['is_active'] = !empty($in['is_active']) ? 1 : 0;
    return [$d, $e];
}

/** Returns which unique field (employee_id / cnic) is already used by another record, or null. */
function staff_find_duplicate($conn, $employee_id, $cnic, $exclude_id = 0) {
    $stmt = mysqli_prepare($conn, "SELECT employee_id, cnic FROM support_staff WHERE (employee_id = ? OR cnic = ?) AND id <> ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ssi', $employee_id, $cnic, $exclude_id);
    mysqli_stmt_execute($stmt);
    $row = db_fetch_assoc(db_stmt_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) return null;
    return strcasecmp($row['employee_id'], $employee_id) === 0 ? 'employee_id' : 'cnic';
}

function staff_save_photo($file, $base_dir = '') {
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($file['error'] !== UPLOAD_ERR_OK) return [null, 'The photo could not be uploaded. Please try again.'];
    if ($file['size'] > STAFF_PHOTO_MAX) return [null, 'Photo must be smaller than 2 MB.'];
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) return [null, 'Photo must be a JPG, PNG or WebP image.'];
    $dir = $base_dir . STAFF_PHOTO_DIR;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = 'staff_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) return [null, 'The photo could not be saved. Please try again.'];
    if (function_exists('compressUploadedImage')) compressUploadedImage($dir . $name, 600, 600);
    return [STAFF_PHOTO_DIR . $name, null];
}

function staff_delete_photo($path, $base_dir = '') {
    if (!$path || strpos($path, STAFF_PHOTO_DIR) !== 0 || strpos($path, '..') !== false) return;
    if (is_file($base_dir . $path)) @unlink($base_dir . $path);
}
