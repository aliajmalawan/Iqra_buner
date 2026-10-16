<?php
/**
 * Settings Helper Functions
 * Use these functions to fetch dynamic settings from database
 */

// Global settings cache
$_settings_cache = null;

/**
 * Load all settings from database into cache
 */
function loadSettings() {
    global $conn, $_settings_cache;

    if ($_settings_cache !== null) {
        return $_settings_cache;
    }

    $_settings_cache = [];
    $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $_settings_cache[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $_settings_cache;
}

/**
 * Get a setting value by key
 * @param string $key Setting key
 * @param string $default Default value if setting not found
 * @return string Setting value
 */
function getSetting($key, $default = '') {
    global $_settings_cache;

    if ($_settings_cache === null) {
        loadSettings();
    }

    return isset($_settings_cache[$key]) ? $_settings_cache[$key] : $default;
}

/**
 * Get site name
 */
function getSiteName() {
    return getSetting('site_name', SITE_NAME);
}

/**
 * Get site email
 */
function getSiteEmail() {
    return getSetting('site_email', ADMIN_EMAIL);
}

/**
 * Get site phone
 */
function getSitePhone() {
    return getSetting('site_phone', '03046032207');
}

/**
 * Get site address
 */
function getSiteAddress() {
    return getSetting('site_address', 'Chowki AJK, Pakistan');
}

/**
 * Get office hours
 */
function getOfficeHours() {
    return getSetting('office_hours', 'Mon - Sat: 8:00 AM - 2:00 PM');
}

/**
 * Get social media URLs
 */
function getSocialMedia() {
    return [
        'facebook' => getSetting('facebook_url'),
        'instagram' => getSetting('instagram_url'),
        'youtube' => getSetting('youtube_url'),
        'twitter' => getSetting('twitter_url')
    ];
}

/**
 * Get hero section content
 */
function getHeroContent() {
    return [
        'title' => getSetting('hero_title', 'Lighting the Candle of Knowledge'),
        'description' => getSetting('hero_description', 'Empowering students with quality education and nurturing their potential to become future leaders.'),
        'button_text' => getSetting('hero_button_text', 'Apply Now'),
        'button_link' => getSetting('hero_button_link', 'admission.php'),
        'image' => getSetting('hero_image', 'images/fortschool.jpg')
    ];
}

/**
 * Get statistics
 */
function getStatistics() {
    return [
        'students' => getSetting('stats_students', '500'),
        'teachers' => getSetting('stats_teachers', '50'),
        'courses' => getSetting('stats_courses', '15'),
        'years' => getSetting('stats_years', '10')
    ];
}

/**
 * Get about page content
 */
function getAboutContent() {
    return [
        'description' => getSetting('about_description', SITE_NAME . ' is a leading educational institution committed to excellence in education...'),
        'mission' => getSetting('mission_statement', 'To provide quality education and empower students to achieve their full potential.'),
        'vision' => getSetting('vision_statement', 'To be a center of educational excellence that nurtures future leaders and innovators.')
    ];
}

/**
 * Get footer content
 */
function getFooterContent() {
    return [
        'about_text' => getSetting('footer_about_text', SITE_NAME . ' is dedicated to providing quality education and nurturing the potential of every student.'),
        'copyright' => getSetting('copyright_text', SITE_NAME . '. All rights reserved.')
    ];
}

/**
 * Get leadership messages
 */
function getLeadershipMessages() {
    global $conn;

    $query = "SELECT * FROM leadership WHERE status = 'active' ORDER BY display_order ASC";
    $result = mysqli_query($conn, $query);

    $leaders = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $leaders[] = $row;
        }
    }

    return $leaders;
}

/**
 * Get hero carousel slides
 */
function getHeroCarouselSlides() {
    global $conn;

    $query = "SELECT * FROM hero_carousel WHERE status = 'active' ORDER BY display_order ASC";
    $result = mysqli_query($conn, $query);

    $slides = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $slides[] = $row;
        }
    }

    return $slides;
}

/**
 * Convert a hex color to an rgba() string
 */
function hexToRgba($hex, $alpha) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "rgba($r,$g,$b,$alpha)";
}

/**
 * Get Digital Management System feature cards
 */
function getDmsFeatures() {
    global $conn;

    $query = "SELECT * FROM dms_features WHERE status = 'active' ORDER BY display_order ASC";
    $result = mysqli_query($conn, $query);

    $features = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $features[] = $row;
        }
    }

    return $features;
}

/**
 * Get Why Choose Us feature tiles
 */
function getWhyChooseUs() {
    global $conn;

    $query = "SELECT * FROM why_choose_us WHERE status = 'active' ORDER BY display_order ASC";
    $result = mysqli_query($conn, $query);

    $tiles = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $tiles[] = $row;
        }
    }

    return $tiles;
}

/**
 * Get Why Choose Us stat pills (e.g. Pass Rate / Students / Years)
 */
function getWcuStats() {
    return [
        ['number' => getSetting('wcu_stat1_number', '98%'),  'label' => getSetting('wcu_stat1_label', 'Pass Rate')],
        ['number' => getSetting('wcu_stat2_number', '500+'), 'label' => getSetting('wcu_stat2_label', 'Students')],
        ['number' => getSetting('wcu_stat3_number', '15+'),  'label' => getSetting('wcu_stat3_label', 'Years Exp.')]
    ];
}

/**
 * Get About page stats strip (98% / 500+ / 40+ / 15+ row)
 */
function getAboutStats() {
    return [
        ['number' => getSetting('about_stat1_number', '98%'),  'label' => getSetting('about_stat1_label', 'Board Pass Rate')],
        ['number' => getSetting('about_stat2_number', '500+'), 'label' => getSetting('about_stat2_label', 'Students Enrolled')],
        ['number' => getSetting('about_stat3_number', '40+'),  'label' => getSetting('about_stat3_label', 'Expert Faculty')],
        ['number' => getSetting('about_stat4_number', '15+'),  'label' => getSetting('about_stat4_label', 'Years of Excellence')]
    ];
}

/**
 * Get Admission page process steps (4 items)
 */
function getAdmissionSteps() {
    return [
        ['title' => getSetting('adm_step1_title', 'Fill Application'), 'desc' => getSetting('adm_step1_desc', 'Complete this form with accurate info')],
        ['title' => getSetting('adm_step2_title', 'Submit Documents'), 'desc' => getSetting('adm_step2_desc', 'Upload or bring originals to office')],
        ['title' => getSetting('adm_step3_title', 'Verification'), 'desc' => getSetting('adm_step3_desc', 'Our team reviews your application')],
        ['title' => getSetting('adm_step4_title', 'Confirmation'), 'desc' => getSetting('adm_step4_desc', 'Get enrolled & start your classes')]
    ];
}

/**
 * Get Admission page required documents checklist
 */
function getAdmissionDocuments() {
    $default = "Previous result card / certificate\nBirth certificate or B-Form\nCNIC copy (Parent/Guardian)\n4-6 passport size photographs\nMigration certificate (if needed)\nAdmission fee";
    $raw = getSetting('adm_documents_list', $default);
    $lines = array_filter(array_map('trim', explode("\n", $raw)));
    return array_values($lines);
}

/**
 * Get fee structure cards for the Admission page
 */
function getFeeStructure() {
    global $conn;
    $query = "SELECT * FROM fee_structure WHERE status = 'active' ORDER BY display_order ASC, id ASC";
    $result = mysqli_query($conn, $query);
    $fees = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $fees[] = $row;
        }
    }
    return $fees;
}

/**
 * Get all active campuses
 */
function getCampuses() {
    global $conn;
    $query = "SELECT * FROM campuses WHERE status = 'active' ORDER BY display_order ASC, id ASC";
    $result = mysqli_query($conn, $query);
    $campuses = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $campuses[] = $row;
        }
    }
    return $campuses;
}

/**
 * Get the campus featured in the Location/Map section
 */
function getMainCampus() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM campuses WHERE status = 'active' AND is_main = 1 ORDER BY display_order ASC LIMIT 1");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    if ($row) return $row;

    $result = mysqli_query($conn, "SELECT * FROM campuses WHERE status = 'active' ORDER BY display_order ASC LIMIT 1");
    return $result ? mysqli_fetch_assoc($result) : null;
}

/**
 * Get Contact page office hours (3 rows: weekday / Saturday / Sunday)
 */
function getContactHours() {
    return [
        ['day' => 'Monday - Friday', 'time' => getSetting('contact_hours_weekday', '8:00 AM - 2:00 PM')],
        ['day' => 'Saturday', 'time' => getSetting('contact_hours_saturday', '8:00 AM - 2:00 PM')],
        ['day' => 'Sunday', 'time' => getSetting('contact_hours_sunday', 'Closed')]
    ];
}

/**
 * Get Contact page form subject dropdown options
 */
function getContactSubjects() {
    $default = "Admission Inquiry\nCourse Information\nFee Inquiry\nGeneral Question\nScholarship\nFeedback\nComplaint\nOther";
    $raw = getSetting('contact_subjects', $default);
    $lines = array_filter(array_map('trim', explode("\n", $raw)));
    return array_values($lines);
}

/**
 * Get Contact page FAQs
 */
function getContactFaqs() {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM contact_faqs WHERE status = 'active' ORDER BY display_order ASC, id ASC");
    $faqs = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $faqs[] = $row;
        }
    }
    return $faqs;
}

/**
 * Get Campuses page stats bar (campus count is computed live)
 */
function getCampusStats() {
    global $conn;
    $count_result = mysqli_query($conn, "SELECT COUNT(*) as c FROM campuses WHERE status = 'active'");
    $count = $count_result ? mysqli_fetch_assoc($count_result)['c'] : 0;

    return [
        ['number' => $count, 'label' => 'Campuses'],
        ['number' => getSetting('camp_stat_students', '2,300+'), 'label' => 'Total Students'],
        ['number' => getSetting('camp_stat_years', '15+'), 'label' => 'Years of Education'],
        ['number' => getSetting('camp_stat_passrate', '98%'), 'label' => 'Pass Rate']
    ];
}

/**
 * Wrap one occurrence of $highlight inside $text with a tag, for two-tone section titles.
 * Escapes both parts first, so the result is always safe to echo raw.
 */
function renderHighlightedTitle($text, $highlight, $tag = 'em') {
    $escaped = htmlspecialchars($text);
    $hl = trim($highlight);
    if ($hl !== '') {
        $escapedHl = htmlspecialchars($hl);
        $pos = stripos($escaped, $escapedHl);
        if ($pos !== false) {
            $escaped = substr($escaped, 0, $pos) . "<$tag>" . substr($escaped, $pos, strlen($escapedHl)) . "</$tag>" . substr($escaped, $pos + strlen($escapedHl));
        }
    }
    return $escaped;
}

/**
 * Replace the {site_name} placeholder inside admin-editable text with the live site name
 */
function applySiteNamePlaceholder($text) {
    return str_replace('{site_name}', getSiteName(), $text);
}

/**
 * Get Stats section header text (home page "Numbers That Speak for Themselves")
 */
function getStatsHeader() {
    return [
        'badge' => getSetting('stats_badge', 'Our Impact'),
        'title' => getSetting('stats_title', 'Numbers That Speak for Themselves'),
        'highlight' => getSetting('stats_title_highlight', 'Speak'),
        'sub_students' => getSetting('stats_sub_students', 'Enrolled & Growing'),
        'sub_teachers' => getSetting('stats_sub_teachers', 'Qualified & Experienced'),
        'sub_courses' => getSetting('stats_sub_courses', 'Diverse Programs'),
        'sub_years' => getSetting('stats_sub_years', 'Trusted by Families')
    ];
}

/**
 * Get Digital Management System section header + mobile app CTA text
 */
function getDmsHeader() {
    return [
        'badge' => getSetting('dms_badge', 'Digital Innovation'),
        'title_line1' => getSetting('dms_title_line1', 'Complete Digital Campus'),
        'title_line2' => getSetting('dms_title_line2', 'Management System'),
        'subtitle' => getSetting('dms_subtitle', '{site_name} is fully digitized — experience modern education with cutting-edge technology at your fingertips.'),
        'cta_title' => getSetting('dms_cta_title', 'Download Our Mobile App'),
        'cta_desc' => getSetting('dms_cta_desc', "Access all school features on your smartphone. Stay connected with your child's education anytime, anywhere."),
        'playstore_link' => getSetting('dms_playstore_link', '#')
    ];
}

/**
 * Get Leadership section header text
 */
function getLeadershipHeader() {
    return [
        'badge' => getSetting('leadership_badge', 'Our Leaders'),
        'title' => getSetting('leadership_title', 'Message from Our Leadership'),
        'highlight' => getSetting('leadership_title_highlight', 'Leadership'),
        'subtitle' => getSetting('leadership_subtitle', 'Words of wisdom and inspiration from the people who guide our institution')
    ];
}

/**
 * Get Why Choose Us section badge + intro description
 */
function getWcuHeader() {
    return [
        'badge' => getSetting('wcu_badge', 'Why IQRA?'),
        'description' => getSetting('wcu_description', 'We combine academic excellence with modern teaching methods to prepare students for a bright and successful future.')
    ];
}

/**
 * Get bottom CTA section text (home page "Ready to Start Your Educational Journey?")
 */
function getCtaSection() {
    return [
        'badge' => getSetting('cta_badge', 'Begin Your Journey'),
        'heading_line1' => getSetting('cta_heading_line1', 'Ready to Start Your'),
        'heading_line2' => getSetting('cta_heading_line2', 'Educational Journey?'),
        'subtext' => getSetting('cta_subtext', 'Join {site_name} today and unlock your full potential with quality education, dedicated teachers, and a supportive community.'),
        'pill1' => getSetting('cta_pill1', 'Admissions Open'),
        'pill2' => getSetting('cta_pill2', 'Scholarship Available'),
        'pill3' => getSetting('cta_pill3', 'Expert Faculty'),
        'pill4' => getSetting('cta_pill4', 'Digital Campus')
    ];
}

/**
 * Get About page hero banner text
 */
function getAboutHero() {
    return [
        'badge' => getSetting('about_hero_badge', 'About IQRA'),
        'subtitle' => getSetting('about_hero_subtitle', 'Learn about our mission, vision, and commitment to providing quality education that shapes futures and transforms lives.'),
        'image' => getSetting('about_hero_image', 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=1600')
    ];
}

/**
 * Get About page "Who We Are" section content
 */
function getAboutWho() {
    return [
        'badge' => getSetting('about_who_badge', 'Who We Are'),
        'title' => getSetting('about_who_title', 'Shaping Futures Through Quality Education'),
        'title_highlight' => getSetting('about_who_title_highlight', 'Quality Education'),
        'paragraph2' => getSetting('about_who_paragraph2', 'With experienced faculty, modern facilities, and a student-centered approach, we create an environment where every student can thrive and reach their full potential.'),
        'hl1' => getSetting('about_who_hl1', 'Board-Aligned Curriculum'),
        'hl2' => getSetting('about_who_hl2', 'Expert Faculty'),
        'hl3' => getSetting('about_who_hl3', 'Digital Campus'),
        'hl4' => getSetting('about_who_hl4', 'Affordable Fees'),
        'image' => getSetting('about_image', 'images/about_us.jpg'),
        'image_badge_num' => getSetting('about_image_badge_num', '15+'),
        'image_badge_label' => getSetting('about_image_badge_label', 'Years of Excellence')
    ];
}

/**
 * Get About page "Explore More" section content (badge/title/subtitle + 3 cards)
 */
function getAboutExplore() {
    return [
        'badge' => getSetting('about_explore_badge', 'Learn More'),
        'title' => getSetting('about_explore_title', 'Discover More About Us'),
        'title_highlight' => getSetting('about_explore_title_highlight', 'More About Us'),
        'subtitle' => getSetting('about_explore_subtitle', 'Explore what drives us and who leads us'),
        'card1_title' => getSetting('about_explore_card1_title', 'Mission & Vision'),
        'card1_desc' => getSetting('about_explore_card1_desc', 'The guiding principles and long-term direction that drive everything we do.'),
        'card2_title' => getSetting('about_explore_card2_title', 'Core Values'),
        'card2_desc' => getSetting('about_explore_card2_desc', 'The principles of excellence, integrity, and respect that shape our culture.'),
        'card3_title' => getSetting('about_explore_card3_title', 'Our Leadership'),
        'card3_desc' => getSetting('about_explore_card3_desc', 'Meet the people guiding our institution and hear their message.')
    ];
}

/**
 * Get About page bottom CTA section content
 */
function getAboutCta() {
    return [
        'badge' => getSetting('about_cta_badge', 'Join Us'),
        'heading_line1' => getSetting('about_cta_heading_line1', 'Join Our Educational'),
        'heading_line2' => getSetting('about_cta_heading_line2', 'Community'),
        'subtext' => getSetting('about_cta_subtext', 'Discover how {site_name} can help you achieve your academic goals with quality education and dedicated support.'),
        'pill1' => getSetting('about_cta_pill1', 'Admissions Open'),
        'pill2' => getSetting('about_cta_pill2', 'Scholarships Available')
    ];
}

/**
 * Get Courses page hero banner text
 */
function getCoursesHero() {
    return [
        'badge' => getSetting('courses_hero_badge', 'Programs Offered'),
        'subtitle' => getSetting('courses_hero_subtitle', 'Browse every program we currently offer — with real duration, fees and eligibility — to find out what you can study here.')
    ];
}

/**
 * Get Courses page "Admission Requirements" section content
 */
function getCoursesRequirements() {
    return [
        'badge' => getSetting('courses_req_badge', 'Documents Needed'),
        'title' => getSetting('courses_req_title', 'Admission Requirements'),
        'title_highlight' => getSetting('courses_req_title_highlight', 'Requirements'),
        'subtitle' => getSetting('courses_req_subtitle', 'What you need to bring before applying'),
        'req1_head' => getSetting('courses_req1_head', 'Matric Programs'),
        'req1_items' => getSetting('courses_req1_items', "Previous class result card\nBirth certificate or B-Form\n4 passport size photographs\nParent/Guardian CNIC copy\nAdmission fee"),
        'req2_head' => getSetting('courses_req2_head', 'Intermediate Programs'),
        'req2_items' => getSetting('courses_req2_items', "Matric certificate & result card\nCNIC or B-Form\n6 passport size photographs\nMigration certificate (if applicable)\nAdmission & registration fee"),
        'req3_head' => getSetting('courses_req3_head', 'Short Courses'),
        'req3_items' => getSetting('courses_req3_items', "Educational certificates (as applicable)\nCNIC or B-Form copy\n2 passport size photographs\nCourse registration form\nCourse fee"),
    ];
}

/**
 * Get Courses page bottom CTA section content
 */
function getCoursesCta() {
    return [
        'badge' => getSetting('courses_cta_badge', 'Enroll Today'),
        'heading_line1' => getSetting('courses_cta_heading_line1', 'Ready to Start Your'),
        'heading_line2' => getSetting('courses_cta_heading_line2', 'Learning Journey?'),
        'subtext' => getSetting('courses_cta_subtext', 'Apply today and take the first step towards a successful academic future with {site_name}.'),
        'pill1' => getSetting('courses_cta_pill1', 'Admissions Open'),
        'pill2' => getSetting('courses_cta_pill2', 'Scholarships Available')
    ];
}

/**
 * Get Faculty page hero banner text
 */
function getFacultyHero() {
    return [
        'badge' => getSetting('faculty_hero_badge', 'Meet Our Team'),
        'subtitle' => getSetting('faculty_hero_subtitle', 'Meet the real people who will teach your classes — their qualifications, departments and experience.')
    ];
}

/**
 * Get Faculty page stats bar (teacher count is computed live, the other 3 are admin-editable)
 */
function getFacultyStats() {
    return [
        'stat2_number' => getSetting('faculty_stat2_number', '10+'),
        'stat2_label' => getSetting('faculty_stat2_label', 'Avg. Experience (yrs)'),
        'stat3_number' => getSetting('faculty_stat3_number', '98%'),
        'stat3_label' => getSetting('faculty_stat3_label', 'Student Pass Rate'),
        'stat4_number' => getSetting('faculty_stat4_number', '100%'),
        'stat4_label' => getSetting('faculty_stat4_label', 'Board-Qualified')
    ];
}

/**
 * Get Faculty page grid section header ("Meet Our Dedicated Faculty")
 */
function getFacultyGridHeader() {
    return [
        'badge' => getSetting('faculty_grid_badge', 'Our Team'),
        'title' => getSetting('faculty_grid_title', 'Meet Our Dedicated Faculty'),
        'title_highlight' => getSetting('faculty_grid_title_highlight', 'Dedicated Faculty'),
        'subtitle' => getSetting('faculty_grid_subtitle', 'Experienced professionals passionate about shaping future leaders')
    ];
}

/**
 * Get Faculty page "Why Our Faculty Stands Out" section content (header + 6 tiles)
 */
function getFacultyWhy() {
    return [
        'badge' => getSetting('faculty_why_badge', 'Excellence'),
        'title' => getSetting('faculty_why_title', 'Why Our Faculty Stands Out'),
        'title_highlight' => getSetting('faculty_why_title_highlight', 'Stands Out'),
        'subtitle' => getSetting('faculty_why_subtitle', 'Qualities that make our teachers truly exceptional'),
        'tile1_title' => getSetting('faculty_why1_title', 'Highly Qualified'),
        'tile1_desc' => getSetting('faculty_why1_desc', 'All teachers hold advanced degrees and professional certifications in their fields.'),
        'tile2_title' => getSetting('faculty_why2_title', 'Experienced Professionals'),
        'tile2_desc' => getSetting('faculty_why2_desc', 'Years of teaching with proven track records of student success and board results.'),
        'tile3_title' => getSetting('faculty_why3_title', 'Student-Centered'),
        'tile3_desc' => getSetting('faculty_why3_desc', 'Dedicated to understanding individual student needs and adapting methods accordingly.'),
        'tile4_title' => getSetting('faculty_why4_title', 'Continuous Learning'),
        'tile4_desc' => getSetting('faculty_why4_desc', 'Regularly update knowledge through professional development and modern pedagogy.'),
        'tile5_title' => getSetting('faculty_why5_title', 'Clear Communication'),
        'tile5_desc' => getSetting('faculty_why5_desc', 'Make complex concepts easy to understand with effective and engaging teaching.'),
        'tile6_title' => getSetting('faculty_why6_title', 'Supportive Mentors'),
        'tile6_desc' => getSetting('faculty_why6_desc', 'Go beyond teaching to provide guidance, support, and encouragement to every student.')
    ];
}

/**
 * Get Faculty page bottom CTA section content
 */
function getFacultyCta() {
    return [
        'badge' => getSetting('faculty_cta_badge', 'Learn from the Best'),
        'heading_line1' => getSetting('faculty_cta_heading_line1', 'Join {site_name} &'),
        'heading_line2' => getSetting('faculty_cta_heading_line2', 'Experience Expert Teaching'),
        'subtext' => getSetting('faculty_cta_subtext', 'Our faculty is dedicated to your success — apply today and learn from the best educators in the region.'),
        'pill1' => getSetting('faculty_cta_pill1', 'Expert Teachers'),
        'pill2' => getSetting('faculty_cta_pill2', 'Small Class Sizes')
    ];
}

/**
 * Get Examination page header text (title/subtitle/background image)
 */
function getExaminationHeader() {
    return [
        'title' => getSetting('exam_header_title', 'Examination'),
        'subtitle' => getSetting('exam_header_subtitle', 'Datesheets & Board Results'),
        'image' => getSetting('exam_header_image', 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=1200')
    ];
}

/**
 * Get the main navbar structure (top-level items with their active dropdown children),
 * managed via AdminCP > Menu Builder.
 */
function getNavMenu() {
    global $conn;

    $children = [];
    $cq = mysqli_query($conn, "SELECT * FROM menu_items WHERE parent_id IS NOT NULL AND status = 'active' ORDER BY display_order ASC, id ASC");
    if ($cq) {
        while ($row = mysqli_fetch_assoc($cq)) {
            $children[$row['parent_id']][] = $row;
        }
    }

    $menu = [];
    $pq = mysqli_query($conn, "SELECT * FROM menu_items WHERE parent_id IS NULL AND status = 'active' ORDER BY display_order ASC, id ASC");
    if ($pq) {
        while ($row = mysqli_fetch_assoc($pq)) {
            $row['children'] = $children[$row['id']] ?? [];
            $menu[] = $row;
        }
    }

    return $menu;
}

/**
 * Merit Scholarship formula — single source of truth used by both the
 * Scholarships page (reference table) and the Fee Calculator (live lookup),
 * so the tiers only ever need to be changed in one place. Admin-editable via
 * AdminCP > Scholarships > Merit Tiers (table: merit_scholarship_tiers).
 * Same percentage applies to every program type (University/Diploma/F.Sc).
 */
function getMeritScholarshipTiers() {
    global $conn;

    $tiers = [];
    $result = mysqli_query($conn, "SELECT * FROM merit_scholarship_tiers WHERE status = 'active' ORDER BY min_percentage DESC, display_order ASC");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $min = (int) $row['min_percentage'];
            $max = $row['max_percentage'] !== null ? (int) $row['max_percentage'] : null;
            $range = $max === null ? "{$min}% and above" : "{$min}% – {$max}%";
            $tiers[] = ['min' => $min, 'max' => $max, 'range' => $range, 'pct' => (int) $row['discount_pct']];
        }
    }

    // Defensive fallback in case the table is ever empty (e.g. a fresh install
    // that hasn't been seeded yet) so the calculators never silently break.
    if (empty($tiers)) {
        $tiers = [
            ['min' => 90, 'max' => null, 'range' => '90% and above', 'pct' => 35],
            ['min' => 85, 'max' => 89,   'range' => '85% – 89%',     'pct' => 30],
            ['min' => 80, 'max' => 84,   'range' => '80% – 84%',     'pct' => 25],
            ['min' => 75, 'max' => 79,   'range' => '75% – 79%',     'pct' => 20],
            ['min' => 70, 'max' => 74,   'range' => '70% – 74%',     'pct' => 15],
        ];
    }

    return $tiers;
}

/**
 * Look up the merit scholarship percentage for a given marks percentage.
 * Returns 0 if below the lowest qualifying tier (70%).
 */
function calculateMeritScholarshipPercent($marks) {
    $marks = (float) $marks;
    foreach (getMeritScholarshipTiers() as $tier) {
        if ($marks >= $tier['min'] && ($tier['max'] === null || $marks <= $tier['max'])) {
            return $tier['pct'];
        }
    }
    return 0;
}
?>
