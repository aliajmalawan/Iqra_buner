-- ======================================================================
-- IQRA School & College Daggar Buner — Portals, Support Staff & Audit Log
-- Migration for the LIVE database (phpMyAdmin > select database > SQL > Go)
--
-- * Safe to run more than once: every step checks first and skips what
--   already exists. Nothing is dropped and no existing record is changed.
-- * Works on MySQL 5.7 / 8.x and MariaDB 10.x (no "IF NOT EXISTS" column
--   syntax, which MySQL does not support — information_schema checks instead).
-- * The PHP code also creates these columns/tables automatically on first
--   use; running this file simply does it up front.
-- ======================================================================

-- ---------- 1. alumni: reuse the existing table, add the new columns ----------
SET @db := DATABASE();

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='alumni' AND COLUMN_NAME='email') = 0,
  'ALTER TABLE alumni ADD COLUMN email VARCHAR(150) NULL AFTER father_name', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='alumni' AND COLUMN_NAME='country') = 0,
  'ALTER TABLE alumni ADD COLUMN country VARCHAR(100) NULL AFTER job_city', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='alumni' AND COLUMN_NAME='show_public') = 0,
  'ALTER TABLE alumni ADD COLUMN show_public TINYINT(1) NOT NULL DEFAULT 0 AFTER review', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='alumni' AND COLUMN_NAME='admin_notes') = 0,
  'ALTER TABLE alumni ADD COLUMN admin_notes TEXT NULL AFTER status', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='alumni' AND COLUMN_NAME='updated_at') = 0,
  'ALTER TABLE alumni ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- "Additional information" became optional (existing values are kept)
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='alumni' AND COLUMN_NAME='review' AND IS_NULLABLE='NO') = 1,
  'ALTER TABLE alumni MODIFY review TEXT NULL', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='alumni' AND INDEX_NAME='idx_alumni_email') = 0,
  'ALTER TABLE alumni ADD INDEX idx_alumni_email (email)', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='alumni' AND INDEX_NAME='idx_alumni_mobile') = 0,
  'ALTER TABLE alumni ADD INDEX idx_alumni_mobile (mobile_number)', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @sql := IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='alumni' AND INDEX_NAME='idx_alumni_status') = 0,
  'ALTER TABLE alumni ADD INDEX idx_alumni_status (status, passing_year)', 'DO 0');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 2. support_staff: separate from the public `faculty` table ----------
CREATE TABLE IF NOT EXISTS support_staff (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- 3. admin_audit_log: sensitive admin actions ----------
CREATE TABLE IF NOT EXISTS admin_audit_log (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_id INT(11) NULL,
    admin_username VARCHAR(50) NOT NULL DEFAULT '',
    admin_role VARCHAR(20) NOT NULL DEFAULT '',
    action VARCHAR(50) NOT NULL,
    entity VARCHAR(50) NOT NULL,
    entity_ref VARCHAR(60) NULL,
    details VARCHAR(500) NULL,
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    user_agent VARCHAR(255) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_created (created_at),
    KEY idx_audit_entity (entity, entity_ref),
    KEY idx_audit_action (action),
    KEY idx_audit_admin (admin_username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------- 4. Menu links (only added when missing) ----------
-- Student Life > Alumni Portal
INSERT INTO menu_items (label, icon, url, parent_id, open_new_tab, display_order, status)
SELECT 'Alumni Portal', 'fa-user-graduate', 'alumni.php',
       (SELECT id FROM (SELECT id FROM menu_items WHERE label = 'Student Life' AND parent_id IS NULL LIMIT 1) t),
       0, 4, 'active'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url = 'alumni.php')
  AND EXISTS (SELECT 1 FROM menu_items WHERE label = 'Student Life' AND parent_id IS NULL);

-- Campuses > Parents Login (next to Campus Portal). An earlier "Parents Portal" link is renamed.
UPDATE menu_items SET label = 'Parents Login', url = 'parents-login.php' WHERE url = 'parents-portal.php';
INSERT INTO menu_items (label, icon, url, parent_id, open_new_tab, display_order, status)
SELECT 'Parents Login', 'fa-people-roof', 'parents-login.php',
       (SELECT parent_id FROM (SELECT parent_id FROM menu_items WHERE url = 'campus-portal.php' LIMIT 1) t),
       0, 99, 'active'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url = 'parents-login.php')
  AND EXISTS (SELECT 1 FROM menu_items WHERE url = 'campus-portal.php');

-- Portal links are set in AdminCP > EduPortal & ERP > EduPortal Login Links
-- (settings eduportal_parents_url / eduportal_admin_url / eduportal_campus_url).
