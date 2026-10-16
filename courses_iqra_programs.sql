-- IQRA School & College Daggar Buner — programs (Boys & Girls campuses, Play Group to Intermediate)
-- Old college programs are set INACTIVE (kept, not deleted). Everything stays editable in
-- AdminCP > Academics > Courses. Fees left empty until the school confirms them.
UPDATE courses SET status = 'inactive' WHERE category IN ('regular', 'short', 'test');

INSERT INTO courses (name, description, duration, category, icon, department, eligibility, display_order, status) VALUES
('Early Years (Play Group, Nursery & Prep)',
 'A caring start to school life, building early reading, numbers, good habits and confidence through activity-based learning.',
 '3 Years', 'school', 'fa-shapes', 'Boys & Girls Campuses', NULL, 1, 'active'),
('Primary (Class 1 – 5)',
 'Strong foundations in English, Urdu, Mathematics, Science, Islamiat and General Knowledge.',
 '5 Years', 'school', 'fa-book-open-reader', 'Boys & Girls Campuses', NULL, 2, 'active'),
('Middle (Class 6 – 8)',
 'A broader curriculum that prepares students for Matric, with growing focus on Science, Mathematics and English.',
 '3 Years', 'school', 'fa-school', 'Boys & Girls Campuses', 'Class 5 passed', 3, 'active'),
('Matric Science – Biology Group (Class 9 – 10)',
 'Secondary School Certificate with Biology, Chemistry and Physics, the foundation for F.Sc Pre-Medical.',
 '2 Years', 'matric', 'fa-dna', 'Boys & Girls Campuses', 'Class 8 passed', 4, 'active'),
('Matric Science – Computer Group (Class 9 – 10)',
 'Secondary School Certificate with Computer Science, Chemistry and Physics, the foundation for ICS and F.Sc Pre-Engineering.',
 '2 Years', 'matric', 'fa-laptop-code', 'Boys & Girls Campuses', 'Class 8 passed', 5, 'active'),
('F.Sc Pre-Medical',
 'Intermediate with Biology, Chemistry and Physics, for students aiming at medicine, pharmacy and allied health studies.',
 '2 Years', 'inter', 'fa-stethoscope', 'Boys & Girls Campuses', 'Matric (Science)', 6, 'active'),
('F.Sc Pre-Engineering',
 'Intermediate with Mathematics, Physics and Chemistry, for students aiming at engineering and technology.',
 '2 Years', 'inter', 'fa-compass-drafting', 'Boys & Girls Campuses', 'Matric (Science)', 7, 'active'),
('ICS (Computer Science)',
 'Intermediate in Computer Science with Mathematics and Physics or Statistics, for careers in computing and IT.',
 '2 Years', 'inter', 'fa-computer', 'Boys & Girls Campuses', 'Matric (Science)', 8, 'active'),
('F.A (Arts & Humanities)',
 'Intermediate in Arts and Humanities, preparing students for higher studies in social sciences, languages and the arts.',
 '2 Years', 'inter', 'fa-feather-pointed', 'Boys & Girls Campuses', 'Matric (any group)', 9, 'active');

-- Courses page texts (AdminCP > Academics > Courses page settings)
INSERT INTO settings (setting_key, setting_value) VALUES
('courses_hero_badge',    'Programs Offered'),
('courses_hero_subtitle', 'From Play Group to Intermediate — explore the classes and programs offered at our Boys and Girls campuses.'),
('courses_req1_head',  'Play Group to Class 8'),
('courses_req1_items', 'Birth certificate or B-Form copy\nPrevious school result card (if any)\nSchool leaving certificate (if transferring)\n4 passport size photographs\nParent/Guardian CNIC copy'),
('courses_req2_head',  'Matric (Class 9 – 10)'),
('courses_req2_items', 'Class 8 result card\nSchool leaving certificate\nB-Form copy\n4 passport size photographs\nParent/Guardian CNIC copy'),
('courses_req3_head',  'Intermediate (F.Sc / ICS / F.A)'),
('courses_req3_items', 'Matric result card & certificate\nCharacter certificate from previous school\nB-Form or CNIC copy\n6 passport size photographs\nMigration certificate (if from another board)')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
