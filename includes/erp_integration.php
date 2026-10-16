<?php
/**
 * ERP Integration Layer
 * ----------------------------------------------------------------------
 * Investigation finding (see AdminCP > ERP Integration for the same note):
 * This college already uses a third-party student management system —
 * "EduPortal" (apps.eduportal.pk) — but it is currently only embedded as
 * a plain login iframe on campus-portal.php. There is no existing API
 * connection, no stored credentials, and no published API documentation
 * available for it. Nothing here pretends otherwise.
 *
 * This file is therefore an INTEGRATION-READY ADAPTER, not a live
 * integration. Until real API credentials + a request/response contract
 * are obtained from EduPortal (or whatever ERP the college decides to
 * connect), every call below is a safe no-op: it records
 * erp_sync_status = 'not_configured' and returns without making any
 * network request. Enabling "ERP Sync" in AdminCP > ERP Integration
 * (with a real API URL + key) is what switches this from a no-op into
 * an actual HTTP call.
 *
 * The JSON payload shape sent below (student_name, father_name, cnic,
 * phone, email, course_name, application_number, status) and the
 * expected response shape ({success, student_id, message}) are a
 * reasonable generic convention — adjust them to match EduPortal's
 * actual API contract once it is provided.
 */

function getErpConfig() {
    return [
        'enabled'  => getSetting('erp_enabled', '0') === '1',
        'provider' => getSetting('erp_provider_name', 'EduPortal'),
        'api_url'  => getSetting('erp_api_url', ''),
        'api_key'  => getSetting('erp_api_key', ''),
    ];
}

/**
 * Sends a record to the configured ERP endpoint and returns a normalized result.
 * Never throws — network/config problems come back as ['success' => false, 'error' => ...].
 */
function erpSendPayload($endpointPath, array $payload) {
    $cfg = getErpConfig();

    if (!$cfg['enabled'] || $cfg['api_url'] === '') {
        return ['success' => false, 'status' => 'not_configured', 'error' => null, 'student_id' => null];
    }

    $url = rtrim($cfg['api_url'], '/') . '/' . ltrim($endpointPath, '/');
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $cfg['api_key'],
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'status' => 'failed', 'error' => 'Connection error: ' . $curl_error, 'student_id' => null];
    }
    if ($http_code < 200 || $http_code >= 300) {
        return ['success' => false, 'status' => 'failed', 'error' => 'ERP responded with HTTP ' . $http_code, 'student_id' => null];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded) || empty($decoded['success'])) {
        $msg = is_array($decoded) && !empty($decoded['message']) ? $decoded['message'] : 'ERP returned an unrecognized response.';
        return ['success' => false, 'status' => 'failed', 'error' => $msg, 'student_id' => null];
    }

    return ['success' => true, 'status' => 'synced', 'error' => null, 'student_id' => $decoded['student_id'] ?? null];
}

/**
 * Push a single admission application to the ERP. Call this right after
 * a new application is successfully saved. Safe to call even when ERP
 * sync is disabled — it just records 'not_configured' and returns.
 */
function pushAdmissionToErp($conn, $admission_id) {
    $admission_id = intval($admission_id);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT a.*, c.name AS course_name FROM admissions a LEFT JOIN courses c ON c.id = a.course_id WHERE a.id = $admission_id"));
    if (!$row) return;

    $result = erpSendPayload('applicants', [
        'application_number' => $row['application_number'],
        'student_name'       => $row['student_name'],
        'father_name'        => $row['father_name'],
        'cnic_bform'         => $row['cnic_bform'],
        'phone'              => $row['phone'],
        'email'              => $row['email'],
        'course_name'        => $row['course_name'],
        'qualification'      => $row['qualification'],
        'marks_percentage'   => $row['marks_percentage'],
        'status'             => $row['status'],
    ]);

    $status = mysqli_real_escape_string($conn, $result['status']);
    $error = $result['error'] !== null ? "'" . mysqli_real_escape_string($conn, $result['error']) . "'" : 'NULL';
    $erp_id = $result['student_id'] !== null ? "'" . mysqli_real_escape_string($conn, $result['student_id']) . "'" : 'NULL';
    $synced_at = $result['success'] ? 'NOW()' : 'NULL';

    mysqli_query($conn, "UPDATE admissions SET erp_sync_status='$status', erp_student_id=$erp_id, erp_sync_error=$error, erp_synced_at=$synced_at WHERE id = $admission_id");
}

/**
 * Push a converted student record to the ERP. Call this right after a
 * student record is created from an approved application (or added
 * directly). Same safe no-op behavior when sync is disabled.
 */
function pushStudentToErp($conn, $student_id) {
    $student_id = intval($student_id);
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, c.name AS course_name FROM students s LEFT JOIN courses c ON c.id = s.course_id WHERE s.id = $student_id"));
    if (!$row) return;

    $result = erpSendPayload('students', [
        'registration_no'  => $row['registration_no'],
        'full_name'        => $row['full_name'],
        'father_name'      => $row['father_name'],
        'cnic'             => $row['cnic'],
        'gender'           => $row['gender'],
        'date_of_birth'    => $row['date_of_birth'],
        'phone'            => $row['phone'],
        'email'            => $row['email'],
        'course_name'      => $row['course_name'],
        'semester'         => $row['semester'],
        'admission_date'   => $row['admission_date'],
        'status'           => $row['status'],
    ]);

    $status = mysqli_real_escape_string($conn, $result['status']);
    $error = $result['error'] !== null ? "'" . mysqli_real_escape_string($conn, $result['error']) . "'" : 'NULL';
    $erp_id = $result['student_id'] !== null ? "'" . mysqli_real_escape_string($conn, $result['student_id']) . "'" : 'NULL';
    $synced_at = $result['success'] ? 'NOW()' : 'NULL';

    mysqli_query($conn, "UPDATE students SET erp_sync_status='$status', erp_student_id=$erp_id, erp_sync_error=$error, erp_synced_at=$synced_at WHERE id = $student_id");
}
?>
