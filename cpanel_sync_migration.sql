-- =====================================================================
-- cPanel DATABASE SYNC — run this ONCE on your LIVE cPanel database
-- =====================================================================
-- This ONLY adds what's missing since your last upload (new tables +
-- 1 new column). It does NOT touch or overwrite any existing data —
-- safe to run even though you've already added real content live.
--
-- HOW TO RUN:
--   1. Log into cPanel → phpMyAdmin
--   2. Select your live website database (left sidebar)
--   3. Click the "SQL" tab at the top
--   4. Paste this entire file's contents into the box
--   5. Click "Go"
--
-- If a line errors with "table already exists" or "duplicate column",
-- that just means it was already applied — safe to ignore and continue.
-- =====================================================================

-- ── 1. Menu Builder (AdminCP > Menu Builder) ──
CREATE TABLE IF NOT EXISTS menu_items (
  id INT(11) NOT NULL AUTO_INCREMENT,
  label VARCHAR(100) NOT NULL,
  icon VARCHAR(50) DEFAULT 'fa-circle',
  url VARCHAR(255) NOT NULL,
  parent_id INT(11) DEFAULT NULL,
  open_new_tab TINYINT(1) DEFAULT 0,
  display_order INT(11) DEFAULT 0,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY parent_id (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seeded with your current live-ready navbar (safe: only runs if the table was just created and is empty)
INSERT INTO menu_items (id, label, icon, url, parent_id, open_new_tab, display_order, status)
SELECT * FROM (SELECT
    1  AS id, 'Home'            AS label, 'fa-house'           AS icon, 'index.php'           AS url, NULL AS parent_id, 0 AS open_new_tab, 1 AS display_order, 'active' AS status UNION ALL SELECT
    2, 'About',                'fa-circle-info',      'about.php',            NULL, 0, 2, 'active' UNION ALL SELECT
    3, 'Academics',            'fa-book-open',         'courses.php',          NULL, 0, 3, 'active' UNION ALL SELECT
    4, 'Admission',            'fa-file-pen',          'admission.php',        NULL, 0, 4, 'active' UNION ALL SELECT
    5, 'Campuses',             'fa-location-dot',      'campuses.php',         NULL, 0, 5, 'active' UNION ALL SELECT
    6, 'Student Life',         'fa-layer-group',       'events.php',           NULL, 0, 6, 'active' UNION ALL SELECT
    7, 'Gallery',              'fa-images',            'gallery.php',          NULL, 0, 7, 'active' UNION ALL SELECT
    8, 'Contact',              'fa-headset',           'contact.php',          NULL, 0, 8, 'active' UNION ALL SELECT
    9, 'About Us',             'fa-university',        'about.php',            2,    0, 1, 'active' UNION ALL SELECT
    10,'Mission & Vision',     'fa-compass',           'mission-vision.php',   2,    0, 2, 'active' UNION ALL SELECT
    11,'Core Values',          'fa-heart',             'core-values.php',      2,    0, 3, 'active' UNION ALL SELECT
    12,'Leadership',           'fa-users',             'leadership.php',       2,    0, 4, 'active' UNION ALL SELECT
    13,'Our Courses',          'fa-book-open',         'courses.php',          3,    0, 1, 'active' UNION ALL SELECT
    14,'Faculty Members',      'fa-chalkboard-user',   'faculty.php',          3,    0, 2, 'active' UNION ALL SELECT
    15,'Examination',          'fa-file-alt',          'examination.php',      3,    0, 3, 'active' UNION ALL SELECT
    16,'Admission Overview',   'fa-info-circle',       'admission.php',        4,    0, 1, 'active' UNION ALL SELECT
    17,'Downloads',            'fa-download',          'downloads.php',        4,    0, 2, 'active' UNION ALL SELECT
    18,'Notifications',        'fa-bullhorn',          'notifications.php',    4,    0, 3, 'active' UNION ALL SELECT
    19,'Our Campuses',         'fa-building-columns',  'campuses.php',         5,    0, 1, 'active' UNION ALL SELECT
    20,'Campus Portal',        'fa-sign-in-alt',       'campus-portal.php',    5,    0, 2, 'active' UNION ALL SELECT
    21,'Events',               'fa-calendar-alt',      'events.php',           6,    0, 1, 'active' UNION ALL SELECT
    22,'News',                 'fa-newspaper',         'news.php',             6,    0, 2, 'active' UNION ALL SELECT
    24,'Contact Us',           'fa-envelope',          'contact.php',          8,    0, 1, 'active'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM menu_items LIMIT 1);

ALTER TABLE menu_items AUTO_INCREMENT = 33;

-- ── 2. Gallery Categories (AdminCP > Gallery) ──
CREATE TABLE IF NOT EXISTS gallery_categories (
  id INT(11) NOT NULL AUTO_INCREMENT,
  slug VARCHAR(50) NOT NULL,
  name VARCHAR(100) NOT NULL,
  icon VARCHAR(50) DEFAULT 'fa-tag',
  display_order INT(11) DEFAULT 0,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO gallery_categories (id, slug, name, icon, display_order, status)
SELECT * FROM (SELECT
    6  AS id, 'examination'         AS slug, 'Examination'         AS name, 'fa-clipboard-list'    AS icon, 5  AS display_order, 'active' AS status UNION ALL SELECT
    7, 'faculty_tour',       'Faculty Tour',        'fa-bus',               6,  'active' UNION ALL SELECT
    8, 'first_aid_training', 'First Aid Training',  'fa-suitcase-medical',  7,  'active' UNION ALL SELECT
    9, 'independence_day',   'Independence Day',    'fa-flag',              8,  'active' UNION ALL SELECT
    10,'mehfil_e_milad',     'Mehfil-e-Milad',       'fa-mosque',            9,  'active' UNION ALL SELECT
    11,'naat_competition',   'Naat Competition',     'fa-microphone',        10, 'active' UNION ALL SELECT
    12,'orientation_class',  'Orientation Class',    'fa-door-open',         11, 'active'
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM gallery_categories LIMIT 1);

ALTER TABLE gallery_categories AUTO_INCREMENT = 14;

-- ── 3. Students (AdminCP > Students) — empty structure only, no seed data ──
CREATE TABLE IF NOT EXISTS students (
  id INT(11) NOT NULL AUTO_INCREMENT,
  registration_no VARCHAR(30) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  father_name VARCHAR(150) NOT NULL,
  cnic VARCHAR(20) DEFAULT NULL,
  gender ENUM('male','female') NOT NULL DEFAULT 'male',
  date_of_birth DATE DEFAULT NULL,
  phone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(100) DEFAULT NULL,
  address TEXT,
  course_id INT(11) DEFAULT NULL,
  semester VARCHAR(30) DEFAULT NULL,
  admission_date DATE DEFAULT NULL,
  photo VARCHAR(255) DEFAULT NULL,
  status ENUM('active','graduated','left','suspended') NOT NULL DEFAULT 'active',
  previous_education VARCHAR(255) DEFAULT NULL,
  special_notes TEXT,
  converted_from_admission_id INT(11) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY registration_no (registration_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. Admission → Student conversion link ──
-- If this errors with "Duplicate column name", it's already applied — ignore and move on.
ALTER TABLE admissions ADD COLUMN converted_student_id INT(11) DEFAULT NULL;
