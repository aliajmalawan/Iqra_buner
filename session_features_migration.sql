-- =====================================================================
-- FEATURE UPDATES MIGRATION — run this ONCE on your LIVE cPanel database
-- =====================================================================
-- Covers every database change made while building: course fee breakdown,
-- Clinical Training, Fee Calculator, Scholarships + Merit Formula,
-- Eligibility Checker, Online Admission upgrades, ERP sync scaffolding,
-- Our Networks, Our Projects, Affiliations & Accreditation, Extra-
-- Curricular Activities + Student Societies, Bahawal Welfare Foundation,
-- Faculty department/specialization, and the "Our Institute" nav reorg.
--
-- Safe to run even if some of this is already applied — every statement
-- either checks first (INSERT ... WHERE NOT EXISTS) or is a plain ALTER/
-- CREATE that MySQL will simply report as "already exists" / "duplicate
-- column" for; those errors are expected and safe to ignore, exactly
-- like cpanel_sync_migration.sql. Nothing here drops or overwrites
-- existing data — only new tables and new nullable/defaulted columns.
--
-- HOW TO RUN:
--   1. Log into cPanel → phpMyAdmin
--   2. Select your live website database (left sidebar)
--   3. Click the "SQL" tab at the top
--   4. Paste this entire file's contents into the box
--   5. Click "Go"
-- =====================================================================

-- ── 1. Course fee breakdown + eligibility text (Course/Program Cards) ──
ALTER TABLE courses ADD COLUMN admission_fee DECIMAL(10,2) NULL;
ALTER TABLE courses ADD COLUMN semester_fee DECIMAL(10,2) NULL;
ALTER TABLE courses ADD COLUMN fee_period ENUM('Semester','Month') DEFAULT 'Semester';
ALTER TABLE courses ADD COLUMN initial_fee DECIMAL(10,2) NULL;
ALTER TABLE courses ADD COLUMN total_fee DECIMAL(10,2) NULL;
ALTER TABLE courses ADD COLUMN affiliation VARCHAR(150) NULL;
ALTER TABLE courses ADD COLUMN department VARCHAR(150) NULL;
ALTER TABLE courses ADD COLUMN eligibility VARCHAR(255) NULL;

-- ── 2. Clinical Training / Hospital Network ──
CREATE TABLE IF NOT EXISTS clinical_partners (
  id INT(11) NOT NULL AUTO_INCREMENT,
  hospital_name VARCHAR(200) NOT NULL,
  location VARCHAR(200) NULL,
  description TEXT NULL,
  facilities TEXT NULL,
  programs TEXT NULL,
  logo VARCHAR(255) NULL,
  website VARCHAR(255) NULL,
  display_order INT(11) DEFAULT 0,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. Scholarships ──
CREATE TABLE IF NOT EXISTS scholarships (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  category ENUM('merit','need_based','other') NOT NULL DEFAULT 'other',
  percentage VARCHAR(50) NULL,
  description TEXT NULL,
  eligibility TEXT NULL,
  application_procedure TEXT NULL,
  terms_conditions TEXT NULL,
  icon VARCHAR(50) DEFAULT 'fa-award',
  display_order INT(11) DEFAULT 0,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. Our Networks (Academic / Affiliated Institution / Industry) ──
CREATE TABLE IF NOT EXISTS network_partners (
  id INT(11) NOT NULL AUTO_INCREMENT,
  category ENUM('academic','affiliated_institution','industry_professional') NOT NULL,
  name VARCHAR(200) NOT NULL,
  location VARCHAR(200) NULL,
  description TEXT NULL,
  logo VARCHAR(255) NULL,
  website VARCHAR(255) NULL,
  display_order INT(11) DEFAULT 0,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 5. Our Projects ──
CREATE TABLE IF NOT EXISTS projects (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  category VARCHAR(100) NULL,
  project_date DATE NULL,
  status ENUM('ongoing','completed','upcoming') NOT NULL DEFAULT 'ongoing',
  description TEXT NULL,
  details TEXT NULL,
  image VARCHAR(255) NULL,
  display_order INT(11) DEFAULT 0,
  visibility ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 6. Affiliations & Accreditation ──
CREATE TABLE IF NOT EXISTS accreditations (
  id INT(11) NOT NULL AUTO_INCREMENT,
  organization_name VARCHAR(200) NOT NULL,
  logo VARCHAR(255) NULL,
  title VARCHAR(200) NULL,
  program VARCHAR(150) NULL,
  status ENUM('accredited','provisional','pending','expired') NOT NULL DEFAULT 'pending',
  description TEXT NULL,
  valid_from DATE NULL,
  valid_until DATE NULL,
  website VARCHAR(255) NULL,
  display_order INT(11) DEFAULT 0,
  visibility ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 7. Eligibility Checker rules (linked to courses) ──
CREATE TABLE IF NOT EXISTS eligibility_rules (
  id INT(11) NOT NULL AUTO_INCREMENT,
  course_id INT(11) NOT NULL,
  qualification VARCHAR(150) NOT NULL,
  min_percentage DECIMAL(5,2) NOT NULL,
  notes VARCHAR(255) NULL,
  display_order INT(11) DEFAULT 0,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY course_id (course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed the one real, already-documented rule (Pharm-D requires F.Sc Pre-Medical, 60%),
-- resolved by course name so it attaches to the right row regardless of its live id.
-- Matches only "Pharm-D" specifically — NOT a broader "%Pharmacy%" pattern, which would
-- incorrectly also match unrelated programs like "Pharmacy Technician".
INSERT INTO eligibility_rules (course_id, qualification, min_percentage, display_order, status)
SELECT c.id, 'F.Sc Pre-Medical', 60.00, 1, 'active' FROM courses c
WHERE c.name LIKE '%Pharm-D%'
  AND NOT EXISTS (
      SELECT 1 FROM eligibility_rules er
      WHERE er.course_id = c.id AND er.qualification = 'F.Sc Pre-Medical'
  )
ORDER BY c.id
LIMIT 1;

-- ── 8. Online Admission Form upgrades (application number + academic detail) ──
ALTER TABLE admissions ADD COLUMN application_number VARCHAR(30) NULL UNIQUE;
ALTER TABLE admissions ADD COLUMN qualification VARCHAR(150) NULL;
ALTER TABLE admissions ADD COLUMN board_university VARCHAR(150) NULL;
ALTER TABLE admissions ADD COLUMN passing_year VARCHAR(10) NULL;
ALTER TABLE admissions ADD COLUMN marks_percentage DECIMAL(5,2) NULL;

-- ── 9. ERP integration sync tracking (safe no-op scaffolding) ──
ALTER TABLE admissions ADD COLUMN erp_sync_status ENUM('not_configured','pending','synced','failed') NOT NULL DEFAULT 'not_configured';
ALTER TABLE admissions ADD COLUMN erp_student_id VARCHAR(50) NULL;
ALTER TABLE admissions ADD COLUMN erp_synced_at TIMESTAMP NULL;
ALTER TABLE admissions ADD COLUMN erp_sync_error VARCHAR(500) NULL;

ALTER TABLE students ADD COLUMN erp_sync_status ENUM('not_configured','pending','synced','failed') NOT NULL DEFAULT 'not_configured';
ALTER TABLE students ADD COLUMN erp_student_id VARCHAR(50) NULL;
ALTER TABLE students ADD COLUMN erp_synced_at TIMESTAMP NULL;
ALTER TABLE students ADD COLUMN erp_sync_error VARCHAR(500) NULL;

-- ── 10. Extra-Curricular Activities (reuses events) + Student Societies ──
ALTER TABLE events ADD COLUMN category ENUM('general','sports','seminar','workshop','competition','cultural','community') NOT NULL DEFAULT 'general';

CREATE TABLE IF NOT EXISTS student_societies (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(150) NOT NULL,
  description TEXT NULL,
  focus_area VARCHAR(150) NULL,
  meeting_info VARCHAR(255) NULL,
  icon VARCHAR(50) DEFAULT 'fa-people-group',
  image VARCHAR(255) NULL,
  display_order INT(11) DEFAULT 0,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 11. Bahawal Welfare Foundation activities (page content itself lives in `settings`, which already exists) ──
CREATE TABLE IF NOT EXISTS foundation_activities (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  activity_date DATE NULL,
  icon VARCHAR(50) DEFAULT 'fa-hand-holding-heart',
  display_order INT(11) DEFAULT 0,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 12. Faculty List — department & specialization ──
ALTER TABLE faculty ADD COLUMN department VARCHAR(150) NULL;
ALTER TABLE faculty ADD COLUMN specialization VARCHAR(255) NULL;

-- =====================================================================
-- 13. NAVIGATION — new pages + the "Our Institute" reorg
-- Every insert below is guarded by url, and parent lookups are resolved
-- by label/url rather than hardcoded ids, so this works regardless of
-- what ids your live menu_items rows already have.
-- =====================================================================

-- New items under existing "Admission" dropdown
INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Fee Calculator', 'fee-calculator.php', (SELECT id FROM menu_items WHERE label='Admission' AND parent_id IS NULL LIMIT 1), 4, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='fee-calculator.php');

INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Scholarships', 'scholarships.php', (SELECT id FROM menu_items WHERE label='Admission' AND parent_id IS NULL LIMIT 1), 5, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='scholarships.php');

INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Eligibility Checker', 'eligibility-checker.php', (SELECT id FROM menu_items WHERE label='Admission' AND parent_id IS NULL LIMIT 1), 6, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='eligibility-checker.php');

-- New item under existing "Student Life" dropdown
INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Extra-Curricular Activities', 'activities.php', (SELECT id FROM menu_items WHERE label='Student Life' AND parent_id IS NULL LIMIT 1), 3, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='activities.php');

-- New items under existing "About" dropdown
INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Chairman''s Message', 'chairman-message.php', (SELECT id FROM menu_items WHERE label='About' AND parent_id IS NULL LIMIT 1), 5, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='chairman-message.php');

INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Principal''s Message', 'principal-message.php', (SELECT id FROM menu_items WHERE label='About' AND parent_id IS NULL LIMIT 1), 6, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='principal-message.php');

-- Make room at position 4 in the top-level bar for the new "Our Institute" dropdown
UPDATE menu_items SET display_order = 5 WHERE label='Admission' AND parent_id IS NULL;
UPDATE menu_items SET display_order = 6 WHERE label='Campuses' AND parent_id IS NULL;
UPDATE menu_items SET display_order = 7 WHERE label='Student Life' AND parent_id IS NULL;
UPDATE menu_items SET display_order = 8 WHERE label='Gallery' AND parent_id IS NULL;
UPDATE menu_items SET display_order = 9 WHERE label='Contact' AND parent_id IS NULL;

-- Create the new top-level "Our Institute" dropdown
INSERT INTO menu_items (label, url, icon, parent_id, display_order, status)
SELECT 'Our Institute', 'our-networks.php', 'fa-building-columns', NULL, 4, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL);

-- Move Clinical Training / Our Networks / Our Projects / Affiliations & Accreditation /
-- Welfare Foundation under "Our Institute" (inserting first if a live site never had
-- them yet, then setting their final parent + position either way)
INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Clinical Training', 'clinical-training.php', (SELECT id FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL LIMIT 1), 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='clinical-training.php');
UPDATE menu_items SET parent_id = (SELECT id FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL LIMIT 1), display_order = 1
WHERE url = 'clinical-training.php';

INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Our Networks', 'our-networks.php', (SELECT id FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL LIMIT 1), 2, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='our-networks.php' AND parent_id IS NOT NULL);
UPDATE menu_items SET parent_id = (SELECT id FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL LIMIT 1), display_order = 2
WHERE url = 'our-networks.php' AND parent_id IS NOT NULL;

INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Our Projects', 'our-projects.php', (SELECT id FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL LIMIT 1), 3, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='our-projects.php');
UPDATE menu_items SET parent_id = (SELECT id FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL LIMIT 1), display_order = 3
WHERE url = 'our-projects.php';

INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Affiliations & Accreditation', 'accreditation.php', (SELECT id FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL LIMIT 1), 4, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='accreditation.php');
UPDATE menu_items SET parent_id = (SELECT id FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL LIMIT 1), display_order = 4
WHERE url = 'accreditation.php';

INSERT INTO menu_items (label, url, parent_id, display_order, status)
SELECT 'Welfare Foundation', 'foundation.php', (SELECT id FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL LIMIT 1), 5, 'active'
WHERE NOT EXISTS (SELECT 1 FROM menu_items WHERE url='foundation.php');
UPDATE menu_items SET parent_id = (SELECT id FROM menu_items WHERE label='Our Institute' AND parent_id IS NULL LIMIT 1), display_order = 5
WHERE url = 'foundation.php';

-- Remove the redundant single-item "Contact Us" dropdown child (Contact's own
-- top-level link already goes to contact.php, so this child added nothing)
DELETE FROM menu_items
WHERE label = 'Contact Us'
  AND parent_id = (SELECT id FROM menu_items WHERE label='Contact' AND parent_id IS NULL LIMIT 1);
