-- Add Digital Management System features & Why Choose Us tables
-- Run this SQL in phpMyAdmin

USE shaheenpublic_db;

CREATE TABLE IF NOT EXISTS `dms_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-star' COMMENT 'Font Awesome class, e.g. fas fa-star',
  `color` varchar(20) DEFAULT '#0B7275' COMMENT 'Hex color for icon and accent',
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed with the cards previously hardcoded on index.php (Digital Management System section)
INSERT INTO `dms_features` (`title`, `description`, `icon`, `color`, `display_order`, `status`) VALUES
('Online Exam Results', 'Instant results & detailed analysis', 'fas fa-chart-line', '#0BBFC6', 1, 'active'),
('Digital Attendance', 'Real-time tracking & reports', 'fas fa-user-check', '#4CAF50', 2, 'active'),
('WhatsApp & SMS', 'Automated alerts to parents', 'fab fa-whatsapp', '#25D366', 3, 'active'),
('Parent Complaints', 'Quick resolution system', 'fas fa-comments', '#FF7043', 4, 'active'),
('Fee Management', 'Online fee history & tracking', 'fas fa-wallet', '#CE93D8', 5, 'active'),
('Digital Diaries', 'Homework & assignments online', 'fas fa-book-open', '#EF5350', 6, 'active'),
('Smart Timetable', 'Dynamic class schedules', 'fas fa-calendar-alt', '#26C6DA', 7, 'active'),
('PTM Notes', 'Parent-teacher meeting records', 'fas fa-clipboard-list', '#FFA726', 8, 'active');

CREATE TABLE IF NOT EXISTS `why_choose_us` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-star' COMMENT 'Font Awesome class, e.g. fas fa-star',
  `color` varchar(20) DEFAULT '#0B7275' COMMENT 'Hex color for icon and accent bar',
  `display_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed with the tiles previously hardcoded on index.php (Why Choose Us section)
INSERT INTO `why_choose_us` (`title`, `description`, `icon`, `color`, `display_order`, `status`) VALUES
('Expert Faculty', 'Highly qualified teachers dedicated to your success', 'fas fa-chalkboard-teacher', '#0B7275', 1, 'active'),
('Modern Facilities', 'State-of-the-art classrooms & labs', 'fas fa-building', '#2e7d32', 2, 'active'),
('Small Classes', 'Personalized attention for every student', 'fas fa-users', '#7b1fa2', 3, 'active'),
('Quality Education', 'Board-aligned curriculum & standards', 'fas fa-certificate', '#e65100', 4, 'active'),
('Proven Results', 'Outstanding board exam track record', 'fas fa-chart-line', '#00695c', 5, 'active'),
('Affordable Fees', 'Scholarships & easy installments', 'fas fa-hand-holding-heart', '#c62828', 6, 'active');
